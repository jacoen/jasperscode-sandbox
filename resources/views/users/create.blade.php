@extends('layouts.app')

@section('content')
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            Create new user
        </div>

        <div class="card-body">
            <form action="#" method="POST" class="px-4">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Your name" value="{{ old('name') }}" autofocus required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="user@email.com" value="{{ old('email') }}" required>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary btn-block text-white fw-semibold">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection