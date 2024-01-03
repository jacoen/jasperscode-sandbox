@extends('layouts.app')

@section('content')

    <x-errors :errors="$errors" />
    <x-flash-success :message="session('success')" />

    <div class="card mb-4">
        <div class="card-header">
            {{ __('My profile') }}
        </div>

        <div class="card-body">
            <div class="col-md-6">
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
    
                    <label for="name" class="form-label">Name</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="name"></span>
                        <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="{{ __('Name') }}"
                            value="{{ old('name', auth()->user()->name) }}" required>
                        @error('name')
                            <span class="invalid-feedback">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
    
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="password"></span>
                        <input class="form-control @error('password') is-invalid @enderror" type="password"
                            name="password" placeholder="{{ __('Password') }}" required>
                        @error('password')
                            <span class="invalid-feedback">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
    
                    <label for="password_confirmation" class="form-label">Confirm password</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text"></span>
                            <input class="form-control @error('password_confirmation') is-invalid @enderror" type="password"
                                   name="password_confirmation" placeholder="{{ __('Confirm Password') }}" required>
                    </div>
    
                    <div class="mb-3 text-end me-1">
                        <button class="btn btn-sm btn-info text-white fw-semibold" type="submit">{{ __('Submit') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <hr />
        <div class="card-body">
            <div class="col-md-6">
                <div class="mb-3">
                    <h5>
                        Two factor authentication
                    </h5>
        
                    <p>
                        {{ __('auth.two_factor_description') }}
                    </p>
                    <p>
                        Two factor authentication is {{ auth()->user()->two_factor_enabled ? 'enabled' : 'disabled' }}
                    </p>
    
                    <form action="{{ route('two-factor.update') }}" method="POST" class="mb-3" 
                        onclick="return confirm('{{ auth()->user()->two_factor_enabled ? __('messages.confirm_disable') : __('messages.confirm_logout') }}')">
                        @csrf
                        @method('PUT')
    
                        {{-- <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="two_factor_enabled" name="two_factor_enabled" {{ auth()->user()->two_factor_enabled ? 'checked' : ''}}
                                onchange="document.getElementById('two_factor_form').submit();">
                            <label class="form-check-label" for="two_factor_enabled">Enable two factor</label>
                        </div> --}}
    
                        <div class="mb-3 text-center mb-1">
                            <button class="btn btn-sm btn-info text-white fw-semibold" type="submit">
                                {{ auth()->user()->two_factor_enabled ? 'Disable' : 'Enable'}} two factor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
