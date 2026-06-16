<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #111827; font-size: 11px; }
        h1 { font-size: 18px; margin: 0 0 2px; color: #1a4731; }
        .sub { color: #6b7280; margin-bottom: 14px; }
        .cards { width: 100%; margin-bottom: 16px; }
        .card { display: inline-block; width: 23%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-right: 1%; }
        .card .label { color: #6b7280; font-size: 9px; text-transform: uppercase; }
        .card .value { font-size: 18px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #1a4731; color: #fff; text-align: left; padding: 6px; font-size: 10px; }
        td { padding: 5px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        .footer { margin-top: 14px; color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>
    <h1>Verdantia · Greenhouse Report</h1>
    <div class="sub">
        {{ $currentGreenhouse->name ?? 'All greenhouses' }} ·
        {{ ucwords(str_replace('_', ' ', $type)) }} · {{ $from }} → {{ $to }}
    </div>

    <div class="cards">
        <div class="card"><div class="label">Avg Temperature</div><div class="value">{{ $summary['avg_temperature'] }} °C</div></div>
        <div class="card"><div class="label">Avg Humidity</div><div class="value">{{ $summary['avg_humidity'] }} %</div></div>
        <div class="card"><div class="label">Total Alerts</div><div class="value">{{ $summary['total_alerts'] }}</div></div>
        <div class="card"><div class="label">Irrigation Events</div><div class="value">{{ $summary['irrigation_events'] }}</div></div>
    </div>

    <table>
        @if ($type === 'alert_history')
            <thead><tr><th>Time</th><th>Severity</th><th>Parameter</th><th>Value</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr><td>{{ $r->created_at->format('M d, H:i') }}</td><td>{{ ucfirst($r->severity) }}</td><td>{{ str_replace('_', ' ', $r->parameter) }}</td><td>{{ $r->value }}</td><td>{{ ucfirst($r->status) }}</td></tr>
                @endforeach
            </tbody>
        @elseif ($type === 'actuator_log')
            <thead><tr><th>Time</th><th>Actuator</th><th>Command</th><th>Source</th><th>Duration</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr><td>{{ $r->created_at->format('M d, H:i') }}</td><td>{{ str_replace('_', ' ', $r->actuator) }}</td><td>{{ strtoupper($r->command) }}</td><td>{{ ucfirst($r->source) }}</td><td>{{ $r->duration ? $r->duration.'s' : '—' }}</td><td>{{ ucfirst($r->status) }}</td></tr>
                @endforeach
            </tbody>
        @else
            <thead><tr><th>Timestamp</th><th>Temp</th><th>Humidity</th><th>Soil</th><th>Water</th><th>Gas</th><th>Rain</th></tr></thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr><td>{{ $r->recorded_at->format('M d, H:i') }}</td><td>{{ $r->temperature ?? '—' }}</td><td>{{ $r->humidity ?? '—' }}</td><td>{{ $r->soil_moisture ?? '—' }}</td><td>{{ $r->water_level_cm ?? '—' }}</td><td>{{ $r->gas_level ?? '—' }}</td><td>{{ $r->rain ?? '—' }}</td></tr>
                @endforeach
            </tbody>
        @endif
    </table>

    <div class="footer">Generated {{ now()->format('M d, Y H:i') }} · Verdantia Greenhouse OS · {{ $rows->count() }} rows</div>
</body>
</html>
