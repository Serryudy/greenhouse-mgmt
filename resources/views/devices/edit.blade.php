@extends('layouts.app')

@section('title', 'Edit Device')
@section('subtitle', $device->name)

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="gh-card">
                <form method="POST" action="{{ route('devices.update', $device) }}">
                    @csrf
                    @method('PUT')
                    @include('devices._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent">Save</button>
                        <a href="{{ route('devices.show', $device) }}" class="btn btn-soft">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
