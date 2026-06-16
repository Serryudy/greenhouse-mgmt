<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Device;
use App\Models\SensorReading;
use App\Models\Threshold;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorDataController extends Controller
{
    /**
     * Parameters that are evaluated against greenhouse thresholds.
     */
    private const EVALUATED_PARAMETERS = [
        'temperature',
        'humidity',
        'soil_moisture',
        'water_level_cm',
        'gas_level',
    ];

    /**
     * Human-friendly labels used when building alert messages.
     */
    private const PARAMETER_LABELS = [
        'temperature' => 'Temperature',
        'humidity' => 'Humidity',
        'soil_moisture' => 'Soil moisture',
        'water_level_cm' => 'Water level',
        'gas_level' => 'Gas level',
    ];

    /**
     * Ingest a sensor-data payload from an authenticated ESP32 device.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\Device $device */
        $device = $request->attributes->get('device');

        $validated = $request->validate([
            'recorded_at' => ['nullable', 'date'],
            'readings' => ['required', 'array'],
            'readings.temperature' => ['nullable', 'numeric'],
            'readings.humidity' => ['nullable', 'numeric'],
            'readings.soil_moisture' => ['nullable', 'numeric'],
            'readings.water_level_cm' => ['nullable', 'numeric'],
            'readings.gas_level' => ['nullable', 'numeric'],
            'readings.rain' => ['nullable', 'numeric'],
            'readings.motion' => ['nullable', 'numeric'],
        ]);

        $readings = $validated['readings'];

        $recordedAt = ! empty($validated['recorded_at'])
            ? Carbon::parse($validated['recorded_at'])
            : now();

        $reading = SensorReading::create([
            'device_id' => $device->id,
            'temperature' => $readings['temperature'] ?? null,
            'humidity' => $readings['humidity'] ?? null,
            'soil_moisture' => $readings['soil_moisture'] ?? null,
            'water_level_cm' => $readings['water_level_cm'] ?? null,
            'gas_level' => $readings['gas_level'] ?? null,
            'rain' => $readings['rain'] ?? null,
            'motion' => isset($readings['motion']) ? (bool) $readings['motion'] : null,
            'raw_payload' => $request->all(),
            'recorded_at' => $recordedAt,
        ]);

        $alertsTriggered = $this->evaluateThresholds($reading, $device);

        return response()->json([
            'status' => 'ok',
            'reading_id' => $reading->id,
            'server_time' => now()->toIso8601String(),
            'alerts_triggered' => $alertsTriggered,
        ]);
    }

    /**
     * Evaluate a reading against its greenhouse thresholds, managing alert
     * lifecycle (create / deduplicate / escalate / resolve).
     *
     * @return int Number of new alerts created.
     */
    private function evaluateThresholds(SensorReading $reading, Device $device): int
    {
        $greenhouse = $device->greenhouse;

        if (! $greenhouse) {
            return 0;
        }

        $thresholds = $greenhouse->thresholds()
            ->whereIn('parameter', self::EVALUATED_PARAMETERS)
            ->get();

        $created = 0;

        foreach ($thresholds as $threshold) {
            $parameter = $threshold->parameter;
            $value = $reading->{$parameter};

            if ($value === null) {
                continue;
            }

            $evaluation = $this->classify((float) $value, $threshold);

            // Most recent still-active alert for this device + parameter.
            $existing = Alert::where('device_id', $device->id)
                ->where('parameter', $parameter)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            // Value is back within warning bounds: resolve any open alert.
            if ($evaluation === null) {
                if ($existing) {
                    $existing->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                    ]);
                }

                continue;
            }

            [$severity, $direction, $bound] = $evaluation;

            if ($existing) {
                // Same severity already active → deduplicate, skip.
                if ($existing->severity === $severity) {
                    continue;
                }

                // Severity changed (e.g. warning → critical) → resolve old, create new.
                $existing->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);
            }

            Alert::create([
                'greenhouse_id' => $greenhouse->id,
                'device_id' => $device->id,
                'parameter' => $parameter,
                'severity' => $severity,
                'value' => $this->formatValue($value),
                'message' => $this->buildMessage($parameter, $value, $severity, $direction, $bound, $threshold->unit),
                'status' => 'active',
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Classify a value against a threshold.
     *
     * @return array{0:string,1:string,2:float}|null  [severity, direction, bound] or null when normal.
     */
    private function classify(float $value, Threshold $threshold): ?array
    {
        if ($threshold->critical_min !== null && $value < $threshold->critical_min) {
            return ['critical', 'below', $threshold->critical_min];
        }

        if ($threshold->critical_max !== null && $value > $threshold->critical_max) {
            return ['critical', 'above', $threshold->critical_max];
        }

        if ($threshold->warning_min !== null && $value < $threshold->warning_min) {
            return ['warning', 'below', $threshold->warning_min];
        }

        if ($threshold->warning_max !== null && $value > $threshold->warning_max) {
            return ['warning', 'above', $threshold->warning_max];
        }

        return null;
    }

    /**
     * Build a human-readable alert message, e.g.
     * "Soil moisture 18% is below critical threshold of 20%".
     */
    private function buildMessage(string $parameter, $value, string $severity, string $direction, float $bound, ?string $unit): string
    {
        $label = self::PARAMETER_LABELS[$parameter] ?? ucfirst(str_replace('_', ' ', $parameter));
        $unit = $unit ?? '';

        return sprintf(
            '%s %s%s is %s %s threshold of %s%s',
            $label,
            $this->formatValue($value),
            $unit,
            $direction,
            $severity,
            $this->formatValue($bound),
            $unit
        );
    }

    /**
     * Render a numeric value without trailing ".0" noise.
     */
    private function formatValue($value): string
    {
        $float = (float) $value;

        return $float == (int) $float ? (string) (int) $float : (string) $float;
    }
}
