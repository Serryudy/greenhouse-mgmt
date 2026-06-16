<?php

namespace App\Http\Controllers;

use App\Models\Greenhouse;
use App\Models\SensorReading;
use App\Models\Threshold;
use App\Support\ThresholdEvaluator;
use Illuminate\Http\Request;

class ThresholdController extends Controller
{
    /**
     * Parameters surfaced on the thresholds page, with display metadata.
     */
    private const PARAMETERS = [
        'temperature'    => ['label' => 'Temperature',   'unit' => '°C'],
        'humidity'       => ['label' => 'Humidity',      'unit' => '%'],
        'soil_moisture'  => ['label' => 'Soil Moisture', 'unit' => '%'],
        'water_level_cm' => ['label' => 'Water Level',   'unit' => 'cm'],
        'gas_level'      => ['label' => 'Gas Level',      'unit' => 'ppm'],
    ];

    public function index(Request $request)
    {
        $currentGreenhouse = $request->filled('greenhouse')
            ? Greenhouse::find($request->input('greenhouse'))
            : Greenhouse::orderBy('name')->first();

        $thresholds = $currentGreenhouse
            ? $currentGreenhouse->thresholds()->get()->keyBy('parameter')
            : collect();

        $deviceIds = $currentGreenhouse ? $currentGreenhouse->devices()->pluck('id') : collect();
        $latestReading = SensorReading::whereIn('device_id', $deviceIds)->latest('recorded_at')->first();

        // Build display rows; ensure a threshold row exists for each parameter.
        $rows = collect(self::PARAMETERS)->map(function ($meta, $key) use ($thresholds, $latestReading, $currentGreenhouse) {
            $threshold = $thresholds->get($key);
            $value = $latestReading?->{$key};

            return [
                'parameter' => $key,
                'label' => $meta['label'],
                'unit' => $threshold->unit ?? $meta['unit'],
                'threshold' => $threshold,
                'current' => $value,
                'status' => ThresholdEvaluator::status($value, $threshold),
            ];
        })->values();

        return view('thresholds.index', compact('currentGreenhouse', 'rows'));
    }

    public function update(Request $request, Threshold $threshold)
    {
        $data = $request->validate([
            'warning_min' => ['nullable', 'numeric'],
            'warning_max' => ['nullable', 'numeric'],
            'critical_min' => ['nullable', 'numeric'],
            'critical_max' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:16'],
        ]);

        $threshold->update($data);

        return redirect()
            ->route('thresholds.index', ['greenhouse' => $threshold->greenhouse_id])
            ->with('status', 'Threshold updated.');
    }
}
