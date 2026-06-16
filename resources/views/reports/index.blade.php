@extends('layouts.app')

@section('title', 'Reports')
@section('subtitle', 'Historical data, exports & summaries')

@section('content')
    {{-- Filter bar --}}
    <div class="gh-card mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold">Greenhouse</label>
                <select name="greenhouse" class="form-select">
                    @foreach ($greenhouses as $gh)
                        <option value="{{ $gh->id }}" @selected(optional($currentGreenhouse)->id == $gh->id)>{{ $gh->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold">Report Type</label>
                <select name="type" class="form-select">
                    <option value="sensor_history" @selected($type === 'sensor_history')>Sensor History</option>
                    <option value="alert_history" @selected($type === 'alert_history')>Alert History</option>
                    <option value="actuator_log" @selected($type === 'actuator_log')>Actuator Log</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-accent w-100" type="submit">Generate</button>
            </div>
        </form>
    </div>

    @if ($generated)
        {{-- Summary cards --}}
        <div class="row g-3 mb-3">
            @foreach ([
                ['Avg Temperature', $summary['avg_temperature'].' °C', 'thermometer', 'tone-temp'],
                ['Avg Humidity', $summary['avg_humidity'].' %', 'droplet', 'tone-hum'],
                ['Total Alerts', $summary['total_alerts'], 'bell', 'tone-soil'],
                ['Irrigation Events', $summary['irrigation_events'], 'droplet', 'tone-water'],
            ] as [$lbl, $val, $icon, $tone])
                <div class="col-6 col-xl-3">
                    <div class="gh-card stat-card h-100">
                        <span class="stat-icon {{ $tone }}">@include('partials.icon', ['name' => $icon, 'size' => 20])</span>
                        <div class="stat-name mt-3">{{ $lbl }}</div>
                        <div class="stat-value mt-1" style="font-size:1.7rem;">{{ $val }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Chart --}}
        <div class="gh-card mb-3">
            <div class="section-title mb-3">Average Temperature · {{ $from }} → {{ $to }}</div>
            <div style="position:relative; height:240px;"><canvas id="reportChart"></canvas></div>
        </div>

        {{-- Data table + exports --}}
        <div class="gh-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title text-capitalize">{{ str_replace('_', ' ', $type) }}</div>
                <div class="d-flex gap-2">
                    @foreach (['pdf' => 'file', 'csv' => 'download'] as $fmt => $icon)
                        <form method="POST" action="{{ route('reports.export.'.$fmt) }}">
                            @csrf
                            <input type="hidden" name="greenhouse" value="{{ optional($currentGreenhouse)->id }}">
                            <input type="hidden" name="from" value="{{ $from }}">
                            <input type="hidden" name="to" value="{{ $to }}">
                            <input type="hidden" name="type" value="{{ $type }}">
                            <button class="btn btn-soft" type="submit">@include('partials.icon', ['name' => $icon, 'size' => 15]) Export {{ strtoupper($fmt) }}</button>
                        </form>
                    @endforeach
                </div>
            </div>

            <div class="table-responsive">
                <table class="gh-table">
                    @if ($type === 'alert_history')
                        <thead><tr><th>Time</th><th>Severity</th><th>Parameter</th><th>Value</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr>
                                    <td class="text-muted-2">{{ $r->created_at->format('M d, H:i') }}</td>
                                    <td>@include('partials.badge', ['status' => $r->severity])</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $r->parameter) }}</td>
                                    <td class="mono">{{ $r->value }}</td>
                                    <td>@include('partials.badge', ['status' => $r->status])</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted-2 py-4">No data in range.</td></tr>
                            @endforelse
                        </tbody>
                    @elseif ($type === 'actuator_log')
                        <thead><tr><th>Time</th><th>Actuator</th><th>Command</th><th>Source</th><th>Duration</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr>
                                    <td class="text-muted-2">{{ $r->created_at->format('M d, H:i') }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $r->actuator) }}</td>
                                    <td class="fw-semibold text-uppercase">{{ $r->command }}</td>
                                    <td><span class="pill-source pill-{{ $r->source }}">{{ ucfirst($r->source) }}</span></td>
                                    <td>{{ $r->duration ? $r->duration.'s' : '—' }}</td>
                                    <td>@include('partials.badge', ['status' => $r->status])</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted-2 py-4">No data in range.</td></tr>
                            @endforelse
                        </tbody>
                    @else
                        <thead><tr><th>Timestamp</th><th>Temp</th><th>Humidity</th><th>Soil</th><th>Water</th><th>Gas</th><th>Rain</th></tr></thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr>
                                    <td class="text-muted-2">{{ $r->recorded_at->format('M d, H:i') }}</td>
                                    <td>{{ $r->temperature ?? '—' }}</td>
                                    <td>{{ $r->humidity ?? '—' }}</td>
                                    <td>{{ $r->soil_moisture ?? '—' }}</td>
                                    <td>{{ $r->water_level_cm ?? '—' }}</td>
                                    <td>{{ $r->gas_level ?? '—' }}</td>
                                    <td>{{ $r->rain ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted-2 py-4">No data in range.</td></tr>
                            @endforelse
                        </tbody>
                    @endif
                </table>
            </div>
            @if ($rows->count() >= 500)
                <div class="text-muted-2 mt-2" style="font-size:.8rem;">Showing first 500 rows — export for the full dataset.</div>
            @endif
        </div>
    @else
        <div class="gh-card text-center text-muted-2 py-5">
            @include('partials.icon', ['name' => 'chart', 'size' => 32])
            <div class="mt-2">Choose filters above and click <strong>Generate</strong> to build a report.</div>
        </div>
    @endif
@endsection

@push('scripts')
@if ($generated)
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const reportChart = @json($chart);
    new Chart(document.getElementById('reportChart'), {
        type: 'line',
        data: {
            labels: reportChart.labels,
            datasets: [{ label: 'Avg Temp °C', data: reportChart.values, borderColor: '#2d7a4f', backgroundColor: 'rgba(45,122,79,0.10)', fill: true, tension: 0.35, pointRadius: 2 }],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } } } },
    });
    });
</script>
@endif
@endpush
