@extends('layouts.guest')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <x-errors :errors="$errors" />

            <div class="card mb-4 px-4">
                <div class="card-body">
                    <h1>Activate account</h1>
                    <form action="{{ route('activate-account.store') }}" method="POST">
                        @csrf
                        <input type="hidden" readonly name="password_token" value="{{ $token }}">
                    
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        </div>
                    
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        </div>
                    
                        <div class="mb-3">
                            <label for="password-confirm" class="form-label">Confirm password</label>
                            <input type="password" id="password-confirm" name="password_confirmation" class="form-control">
                        </div>
                    
                        <div class="mb-3">
                            <button type="submit" class="btn btn-success text-white fw-semibold">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection