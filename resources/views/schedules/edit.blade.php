@extends('layouts.app')

@section('title', 'Edit Schedule')
@section('subtitle', $schedule->name)

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="gh-card">
                <form method="POST" action="{{ route('schedules.update', $schedule) }}">
                    @csrf
                    @method('PUT')
                    @include('schedules._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent">Save</button>
                        <a href="{{ route('schedules.index', ['greenhouse' => $schedule->greenhouse_id]) }}" class="btn btn-soft">Cancel</a>
                    </div>
                </form>
                <hr class="my-4">
                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}"
                      onsubmit="return confirm('Delete this schedule?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" type="submit">
                        @include('partials.icon', ['name' => 'trash', 'size' => 14]) Delete schedule
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
