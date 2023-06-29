@extends('layouts.guest')

@section('content')
  <div class="row">
    <div class="d-flex justify-content-center">
      <div class="col-md-8 col-lg-6">
        <x-errors :errors="$errors" />

        <div class="card mb-4 mx-4">
          <div class="card-body p-4">
            <h1>Reset password</h1>

            <form action="{{ route('password.update') }}" method="POST">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                  <label for="email" class="form-label">Email address</label>
                  <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="password" required>
                  @error('password')
                        <span class="invalid-feedback">
                            {{ $message }}
                        </span>
                        @enderror
                </div>

                <div class="mb-3">
                  <label for="password-confirm" class="form-label">Confirm password</label>
                  <input type="password" id="password-confirm" name="password_confirmation" class="form-control" placeholder="confirm password" required>
                </div>

                <div>
                  <button type="submit" class="btn btn-block btn-primary fw-semibold text-white">
                    Reset password
                  </button>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
