@extends('layouts.app')

@section('content')
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            {{ $user->name }}'s account
        </div>

        <div class="card-body">
            <form action="{{ route('users.update', $user) }}" method="POST" class="px-4">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Your name" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="user@email.com" value="{{ old('email', $user->email) }}" readonly>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" @if($user->hasRole('Super Admin')) disabled @endif>
                        @if($user->roles->isEmpty())
                            <option value="" selected>Select a role...</option>
                        @endif
                        @foreach($roles as $role)     
                                    <option value="{{ $role->id }}" {{ old('role', $user->roles)->contains($role->id) ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                    </select>
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