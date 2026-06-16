@extends('layouts.app')

@section('title', 'Devices')
@section('subtitle', 'ESP32 controllers across all greenhouses')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('devices.create') }}" class="btn btn-accent">
            @include('partials.icon', ['name' => 'plus', 'size' => 16]) Register Device
        </a>
    </div>

    <div class="gh-card">
        <div class="table-responsive">
            <table class="gh-table">
                <thead>
                    <tr><th>Name</th><th>Greenhouse</th><th>Status</th><th>Last Seen</th><th>IP</th><th>API Key</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td class="fw-semibold">{{ $device->name }}<div class="text-muted-2 mono" style="font-size:.78rem;">{{ $device->identifier }}</div></td>
                            <td>{{ $device->greenhouse->name ?? '—' }}</td>
                            <td><span class="status-dot {{ $device->status }}"></span>{{ ucfirst($device->status) }}</td>
                            <td class="text-muted-2">{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="mono">{{ $device->ip_address ?: '—' }}</td>
                            <td>
                                <span class="mono">{{ substr($device->api_key, 0, 8) }}***</span>
                                <button class="btn btn-soft btn-sm ms-1 py-0 px-2" type="button"
                                        onclick="navigator.clipboard.writeText('{{ $device->api_key }}'); this.textContent='Copied';">Copy</button>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('devices.show', $device) }}" class="btn btn-soft btn-sm">View</a>
                                    <a href="{{ route('devices.edit', $device) }}" class="btn btn-soft btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('devices.destroy', $device) }}" class="d-inline"
                                          onsubmit="return confirm('Delete this device?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted-2 text-center py-4">No devices registered.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
