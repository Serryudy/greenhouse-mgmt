@extends('layouts.app')

@section('title', 'Register Device')
@section('subtitle', 'An API key will be generated automatically')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="gh-card">
                <form method="POST" action="{{ route('devices.store') }}">
                    @csrf
                    @include('devices._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent">Register Device</button>
                        <a href="{{ route('devices.index') }}" class="btn btn-soft">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
