@extends('layouts.guest')

@section('content')
    <div class="row">
        <div class="d-flex justify-content-center">
            <div class="col-md-8 col-lg-6">
                <x-errors :errors="$errors" />
                <x-flash-success :message="session('success')" />

                <div class="card mb-4 mx-4">
                    <div class="card-body p-4">
                        <h2>Two factor authentication</h2>
                        <div class="mb-3">
                            <p>
                                You have received an email which contains two factor login code. 
                                If you haven't received it, please click <a href="{{ route ('verify.resend') }}">here</a>.
                            </p>
                            <p>
                                We have sent the two factor code to {{ $email }}
                            </p>
                        </div>

                        <form action="{{ route('verify.store')}}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="two_factor_code" class="form-label">Two factor code</label>
                                <input type="text" id="two_factor_code" name="two_factor_code" class="form-control" placeholder="Enter your 6 digit code here" required> 
                            </div>
    
                            <div class="mb-3">
                                <button class="btn btn-info text-white fw-semibold">
                                    Verify
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection