@extends('layouts.app')

@section('title', $greenhouse->name)
@section('subtitle', $greenhouse->location ?: 'Greenhouse overview')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('greenhouses.edit', $greenhouse) }}" class="btn btn-soft">
            @include('partials.icon', ['name' => 'edit', 'size' => 15]) Edit
        </a>
    </div>

    <div class="gh-card">
        <ul class="nav gh-tabs mb-4" role="tablist">
            <li><button class="gh-tab active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Overview</button></li>
            <li><button class="gh-tab" data-bs-toggle="tab" data-bs-target="#tab-devices" type="button">Devices</button></li>
            <li><button class="gh-tab" data-bs-toggle="tab" data-bs-target="#tab-thresholds" type="button">Thresholds</button></li>
            <li><button class="gh-tab" data-bs-toggle="tab" data-bs-target="#tab-alerts" type="button">Alerts</button></li>
        </ul>

        <div class="tab-content">
            {{-- Overview --}}
            <div class="tab-pane fade show active" id="tab-overview">
                @if ($latestReading)
                    <div class="row g-3">
                        @foreach ([['Temperature','temperature','°C'],['Humidity','humidity','%'],['Soil Moisture','soil_moisture','%'],['Water Level','water_level_cm','cm']] as [$lbl,$key,$unit])
                            <div class="col-6 col-lg-3">
                                <div class="field-block">
                                    <div class="field-label">{{ $lbl }}</div>
                                    <div class="field-value">{{ $latestReading->$key ?? '—' }} {{ $unit }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-muted-2 mt-3" style="font-size:.82rem;">Last reading {{ $latestReading->recorded_at->diffForHumans() }}</div>
                @else
                    <p class="text-muted-2 mb-0">No sensor readings recorded yet.</p>
                @endif
            </div>

            {{-- Devices --}}
            <div class="tab-pane fade" id="tab-devices">
                <table class="gh-table">
                    <thead><tr><th>Name</th><th>Identifier</th><th>Status</th><th>Last Seen</th></tr></thead>
                    <tbody>
                        @forelse ($greenhouse->devices as $d)
                            <tr>
                                <td class="fw-semibold">{{ $d->name }}</td>
                                <td class="mono">{{ $d->identifier }}</td>
                                <td><span class="status-dot {{ $d->status }}"></span>{{ ucfirst($d->status) }}</td>
                                <td class="text-muted-2">{{ $d->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted-2 text-center py-3">No devices.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Thresholds --}}
            <div class="tab-pane fade" id="tab-thresholds">
                <table class="gh-table">
                    <thead><tr><th>Parameter</th><th>Warning Min</th><th>Warning Max</th><th>Critical Min</th><th>Critical Max</th><th>Unit</th></tr></thead>
                    <tbody>
                        @forelse ($greenhouse->thresholds as $t)
                            <tr>
                                <td class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $t->parameter) }}</td>
                                <td>{{ $t->warning_min ?? '—' }}</td>
                                <td>{{ $t->warning_max ?? '—' }}</td>
                                <td>{{ $t->critical_min ?? '—' }}</td>
                                <td>{{ $t->critical_max ?? '—' }}</td>
                                <td class="text-muted-2">{{ $t->unit }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted-2 text-center py-3">No thresholds configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Alerts --}}
            <div class="tab-pane fade" id="tab-alerts">
                <table class="gh-table">
                    <thead><tr><th>Severity</th><th>Parameter</th><th>Message</th><th>Status</th><th class="text-end">Time</th></tr></thead>
                    <tbody>
                        @forelse ($recentAlerts as $a)
                            <tr class="{{ $a->severity === 'critical' ? 'row-critical' : ($a->severity === 'warning' ? 'row-warning' : '') }}">
                                <td>@include('partials.badge', ['status' => $a->severity])</td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $a->parameter) }}</td>
                                <td>{{ $a->message }}</td>
                                <td>@include('partials.badge', ['status' => $a->status])</td>
                                <td class="text-end text-muted-2">{{ $a->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted-2 text-center py-3">No alerts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
