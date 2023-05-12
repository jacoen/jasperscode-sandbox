@extends('layouts.guest')

@section('content')
    <div class="row">
        <div class="d-flex justify-content-center">
            <div class="col-md-8 col-lg-6">
                <x-errors :errors="$errors" />

                <div class="card mb-4 mx-4">
                    <div class="card-body p-4">
                        <h1>Login</h1>
                        <form action="{{ route('login') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" value="{{ old('email') }}" autofocus required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary text-white fw-semibold">
                                        Login
                                    </button>
                                </div>

                                @if (Route::has('password.request'))

                                @endif
                                <div class="col-md-5 offset-md-6">
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        Forgot your password?
                                    </a>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection