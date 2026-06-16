@extends('layouts.app')

@section('title', 'Thresholds')
@section('subtitle', $currentGreenhouse ? 'Alert thresholds · '.$currentGreenhouse->name : 'Alert thresholds')

@section('content')
    <div class="gh-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="section-title">Parameter Thresholds</div>
            <span class="text-muted-2" style="font-size:.82rem;">Rows highlighted by current reading status</span>
        </div>
        <div class="table-responsive">
            <table class="gh-table">
                <thead>
                    <tr><th>Parameter</th><th>Current</th><th>Warning Min</th><th>Warning Max</th><th>Critical Min</th><th>Critical Max</th><th>Unit</th><th class="text-end">Edit</th></tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php $t = $row['threshold']; @endphp
                        <tr class="{{ $row['status'] === 'critical' ? 'row-critical' : ($row['status'] === 'warning' ? 'row-warning' : '') }}">
                            <td class="fw-semibold">{{ $row['label'] }}</td>
                            <td>
                                @if ($row['current'] !== null)
                                    {{ $row['current'] }} {{ $row['unit'] }} @include('partials.badge', ['status' => $row['status']])
                                @else
                                    <span class="text-muted-2">—</span>
                                @endif
                            </td>
                            <td>{{ $t->warning_min ?? '—' }}</td>
                            <td>{{ $t->warning_max ?? '—' }}</td>
                            <td>{{ $t->critical_min ?? '—' }}</td>
                            <td>{{ $t->critical_max ?? '—' }}</td>
                            <td class="text-muted-2">{{ $row['unit'] }}</td>
                            <td class="text-end">
                                @if ($t)
                                    <button class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#editThreshold{{ $t->id }}">Edit</button>
                                @else
                                    <span class="text-muted-2" style="font-size:.8rem;">not set</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Edit modals --}}
    @foreach ($rows as $row)
        @php $t = $row['threshold']; @endphp
        @if ($t)
            <div class="modal fade" id="editThreshold{{ $t->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content" style="border-radius:12px;">
                        <form method="POST" action="{{ route('thresholds.update', $t) }}">
                            @csrf @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $row['label'] }} thresholds</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    @foreach ([['warning_min','Warning Min'],['warning_max','Warning Max'],['critical_min','Critical Min'],['critical_max','Critical Max']] as [$field,$lbl])
                                        <div class="col-6">
                                            <label class="form-label fw-semibold">{{ $lbl }}</label>
                                            <input type="number" step="any" name="{{ $field }}" value="{{ $t->$field }}" class="form-control">
                                        </div>
                                    @endforeach
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Unit</label>
                                        <input type="text" name="unit" value="{{ $t->unit }}" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-accent">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection
