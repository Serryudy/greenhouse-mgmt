@extends('layouts.app')

@section('title', 'Add Schedule')
@section('subtitle', 'Create a fertigation / irrigation program')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="gh-card">
                <form method="POST" action="{{ route('schedules.store') }}">
                    @csrf
                    @include('schedules._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent">Save</button>
                        <a href="{{ route('schedules.index') }}" class="btn btn-soft">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
