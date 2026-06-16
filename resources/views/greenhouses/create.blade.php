@extends('layouts.app')

@section('title', 'Add Greenhouse')
@section('subtitle', 'Register a new greenhouse site')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="gh-card">
                <form method="POST" action="{{ route('greenhouses.store') }}">
                    @csrf
                    @include('greenhouses._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent">Save</button>
                        <a href="{{ route('greenhouses.index') }}" class="btn btn-soft">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
