@extends('layouts.guest')

@section('content')
<div class="row">
    <div class="d-flex justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <x-errors :errors="$errors" />

            <div class="card mb-4 mx-4">
                <div class="card-body p-4">
                    <h1>Reset Password</h1>
                    <form action="{{ route('password.email') }}" method="POST">
                        @csrf
                        @if (session('status'))
                            <div role="alert" class="alert alert-success py-2">
                                <ul class="py-0 m-0">
                                    <li>{{ session('status') }}</li>
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email" required>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary btn-block fw-semibold text-white">
                                Send password reset link
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
