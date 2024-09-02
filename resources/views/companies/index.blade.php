@extends('layouts.app')

@section('content')

    <div class="card mb-4">
        <div class="card-header">
            <h3>{{ __('Companies') }}</h3>
        </div>

        <div class="card-body">
            <div class="row align-items-center mb-3">
                <div class="col-lg-2">
                    <a href="#" class="btn btn-block btn-success fw-semibold text-white">
                        New company
                    </a>
                </div>
            </div>

            @if(! $companies->count())
                <p class="ms-2">
                    No clients yet.
                </p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Country</th>
                            <th scope="col">City</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($companies as $company)
                            <x-table-link route="companies.show" :param="$company" :content="$company->name" :limit="35" />
                            <td>{{ $company->country }}</td>
                            <td>{{ $company->city }}</td>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection