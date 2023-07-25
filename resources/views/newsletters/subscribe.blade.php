@extends('layouts.guest')

@section('content')
    <div class="d-flex justify-content-center">
        <div class="col-md-10">
            <x-errors :errors="$errors" />

            <div class="card shadow-sm border-white mb-4 px-4">
                <div>
                    <h1 class="fs-1 card-title mb-3 text-center">Nieuwsbrief</h1>
                </div>
        
                <div class="text-center">
                    <p class="text-break">
                        U kunt zich op deze pagina aanmelden voor de nieuwsbrief
                    </p>
                </div>

                <div class="d-flex align-items-center justify-content-center mb-4">
                    <form action="/newsletter" method="POST" class="row g-2 pb-4">
                        @csrf

                        <div class="col-md-10">
                            <label for="email" class="visually-hidden">Email address</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="your@email.com">
                            @error('email')
                                <div class="invalid-feedback ms-2">
                                    <span class="fw-semibold">
                                        {{ $message }}
                                    </span>
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-info fw-semibold text-white">Subscribe</button>
                        </div>
                    </form>
                </div>                
            </div>
        </div>
    </div>
@endsection