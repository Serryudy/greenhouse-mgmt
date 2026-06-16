@extends('layouts.app')

@section('title', 'Greenhouses')
@section('subtitle', 'Manage your greenhouse sites')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('greenhouses.create') }}" class="btn btn-accent">
            @include('partials.icon', ['name' => 'plus', 'size' => 16]) Add Greenhouse
        </a>
    </div>

    <div class="row g-3">
        @forelse ($greenhouses as $gh)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="gh-card h-100 d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-2">
                        <span class="stat-icon tone-green">@include('partials.icon', ['name' => 'home', 'size' => 22])</span>
                        <div>
                            <div class="fw-bold" style="font-size:1.05rem;">{{ $gh->name }}</div>
                            <div class="text-muted-2" style="font-size:.85rem;">{{ $gh->location ?: 'No location set' }}</div>
                        </div>
                    </div>
                    <p class="text-muted-2 mb-3" style="font-size:.88rem;">{{ $gh->description ?: 'No description.' }}</p>

                    <div class="d-flex gap-3 mb-3 pb-3 border-bottom flex-wrap" style="font-size:.82rem;">
                        <span><strong>{{ $gh->devices_count }}</strong> <span class="text-muted-2">devices</span></span>
                        <span><strong>{{ $gh->active_alerts_count }}</strong> <span class="text-muted-2">active alerts</span></span>
                    </div>

                    <div class="d-flex gap-2 mt-auto">
                        <a href="{{ route('greenhouses.show', $gh) }}" class="btn btn-soft btn-sm flex-fill">View</a>
                        <a href="{{ route('greenhouses.edit', $gh) }}" class="btn btn-soft btn-sm flex-fill">Edit</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="gh-card text-center text-muted-2">No greenhouses yet.</div></div>
        @endforelse
    </div>
@endsection
