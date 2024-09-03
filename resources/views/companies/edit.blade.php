@extends('layouts.app')

@section('content')
<x-errors :errors="$errors" />

<div class="card mb-4">
    <div class="card-header">
        <h2>{{ $company->name }}</h2>
    </div>

    <div class="card-body">
        <form action="{{ route('companies.update', $company) }}" method="POST" class="px-4">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Company name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Company name" value="{{ old('name', $company->name) }}" required>
            </div>

            <div class="row gx-3 mb-3">
                <div class="col-md-6">
                    <label for="country" class="form-label">Country</label>
                <input type="text" id="country" name="country" class="form-control @error('country') is-invalid @enderror" placeholder="Country" value="{{ old('country', $company->country) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="city" class="form-label">City</label>
                    <input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror" placeholder="City" value="{{ old('city', $company->city) }}" required>
                </div>
            </div>


            <div class="row gx-3 mb-3">
                <div class="col-md-6">
                    <label for="postal_code" class="form-label">Postcode</label>
                    <input type="text" id="postal_code" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror" placeholder="9999AA" value="{{ old('postal_code', $company->postal_code) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" id="address" name="address" class="form-control @error('address') is-invalid @enderror" placeholder="Pietjepuk straat" value="{{ old('address', $company->address) }}" required>
                </div> 
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Phone number</label>
                <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="+31 12345678" value="{{ old('phone', $company->phone) }}" required>
            </div>

            <div class="row gx-3 mb-3">
                <div class="col-md-6">
                    <label for="contact_name" class="form-label">Contact</label>
                    <input type="text" id="contact_name" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror" placeholder="Contact" value="{{ old('contact_name', $company->contact_name) }}" required>
                </div>

                <div class="col-md-6">
                    <label for="contact_email" class="form-label">Contact email</label>
                    <input type="text" id="contact_email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" placeholder="Contact" value="{{ old('contact_email', $company->contact_email) }}" required>
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn-block btn-primary fw-semibold text-white">Submit</button>
            </div>
        </form>
    </div>
</div>
@endsection