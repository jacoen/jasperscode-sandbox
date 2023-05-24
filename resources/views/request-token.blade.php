@extends('layouts.guest')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <x-errors :errors="$errors" />

            <div class="card mb-4 px-4">
                <div class="card-body">
                    <h1>Request new token</h1>
                    <form action="{{ route('request-token.store') }}" method="POST">
                        @csrf
                        <input type="hidden" readonly name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary btn-block fw-semibold text-white">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection