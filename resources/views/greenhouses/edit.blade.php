@extends('layouts.app')

@section('title', 'Edit Greenhouse')
@section('subtitle', $greenhouse->name)

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="gh-card">
                <form method="POST" action="{{ route('greenhouses.update', $greenhouse) }}">
                    @csrf
                    @method('PUT')
                    @include('greenhouses._form')
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-accent">Save</button>
                            <a href="{{ route('greenhouses.show', $greenhouse) }}" class="btn btn-soft">Cancel</a>
                        </div>
                    </div>
                </form>
                <hr class="my-4">
                <form method="POST" action="{{ route('greenhouses.destroy', $greenhouse) }}"
                      onsubmit="return confirm('Delete this greenhouse and all its data?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        @include('partials.icon', ['name' => 'trash', 'size' => 14]) Delete greenhouse
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
