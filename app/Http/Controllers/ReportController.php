<?php

namespace App\Http\Controllers;

use App\Models\ActuatorCommand;
use App\Models\Alert;
use App\Models\Greenhouse;
use App\Models\SensorReading;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $greenhouses = Greenhouse::orderBy('name')->get();
        $report = $this->buildReport($request);

        return view('reports.index', array_merge($report, [
            'greenhouses' => $greenhouses,
        ]));
    }

    public function exportPdf(Request $request)
    {
        $report = $this->buildReport($request, force: true);

        $pdf = Pdf::loadView('reports.pdf', $report)->setPaper('a4', 'landscape');

        return $pdf->download('greenhouse-report-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $report = $this->buildReport($request, force: true);
        $type = $report['type'];
        $rows = $report['rows'];

        $headers = match ($type) {
            'alert_history' => ['Time', 'Severity', 'Parameter', 'Value', 'Status'],
            'actuator_log' => ['Time', 'Actuator', 'Command', 'Source', 'Duration', 'Status'],
            default => ['Timestamp', 'Temperature', 'Humidity', 'Soil', 'Water', 'Gas', 'Rain'],
        };

        $filename = 'greenhouse-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $rows, $type) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $this->csvRow($type, $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Build the report dataset from request filters.
     */
    private function buildReport(Request $request, bool $force = false): array
    {
        $currentGreenhouse = $request->filled('greenhouse')
            ? Greenhouse::find($request->input('greenhouse'))
            : Greenhouse::orderBy('name')->first();

        $type = $request->input('type', 'sensor_history');
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(7)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        $generated = $force || $request->filled('type') || $request->filled('from') || $request->filled('to');

        $deviceIds = $currentGreenhouse ? $currentGreenhouse->devices()->pluck('id') : collect();

        // Summary cards.
        $readingsQuery = SensorReading::whereIn('device_id', $deviceIds)->whereBetween('recorded_at', [$from, $to]);
        $summary = [
            'avg_temperature' => round((clone $readingsQuery)->avg('temperature') ?? 0, 1),
            'avg_humidity' => round((clone $readingsQuery)->avg('humidity') ?? 0, 1),
            'total_alerts' => Alert::when($currentGreenhouse, fn ($q) => $q->where('greenhouse_id', $currentGreenhouse->id))
                ->whereBetween('created_at', [$from, $to])->count(),
            'irrigation_events' => ActuatorCommand::whereIn('device_id', $deviceIds)
                ->whereIn('actuator', ['pump', 'valve1', 'valve2'])->where('command', 'on')
                ->whereBetween('created_at', [$from, $to])->count(),
        ];

        // Chart: avg temperature per day across the range.
        $daily = (clone $readingsQuery)->orderBy('recorded_at')->get(['temperature', 'recorded_at'])
            ->groupBy(fn ($r) => $r->recorded_at->format('M d'));
        $chart = [
            'labels' => $daily->keys()->values(),
            'values' => $daily->keys()->map(fn ($d) => round($daily[$d]->avg('temperature'), 1))->values(),
        ];

        // Data table rows.
        $rows = collect();
        if ($generated) {
            $rows = match ($type) {
                'alert_history' => Alert::when($currentGreenhouse, fn ($q) => $q->where('greenhouse_id', $currentGreenhouse->id))
                    ->whereBetween('created_at', [$from, $to])->latest('created_at')->limit(500)->get(),
                'actuator_log' => ActuatorCommand::with('device')->whereIn('device_id', $deviceIds)
                    ->whereBetween('created_at', [$from, $to])->latest('created_at')->limit(500)->get(),
                default => (clone $readingsQuery)->latest('recorded_at')->limit(500)->get(),
            };
        }

        return [
            'currentGreenhouse' => $currentGreenhouse,
            'type' => $type,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'generated' => $generated,
            'summary' => $summary,
            'chart' => $chart,
            'rows' => $rows,
        ];
    }

    private function csvRow(string $type, $row): array
    {
        return match ($type) {
            'alert_history' => [
                $row->created_at, $row->severity, $row->parameter, $row->value, $row->status,
            ],
            'actuator_log' => [
                $row->created_at, $row->actuator, $row->command, $row->source, $row->duration, $row->status,
            ],
            default => [
                $row->recorded_at, $row->temperature, $row->humidity,
                $row->soil_moisture, $row->water_level_cm, $row->gas_level, $row->rain,
            ],
        };
    }
}
