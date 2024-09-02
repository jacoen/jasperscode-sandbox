@extends('layouts.app')

@section('content')
<x-errors :errors="$errors" />

<div class="card-mb-4">
    <div class="card-header">
        <h3>{{ __('New company') }}</h3>
    </div>

    <div class="card-body">
        <form action="#" method="POST" class="px-4">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Company name" value="{{ old('name') }}" required>
            </div>
        </form>
    </div>
</div>
@endsection