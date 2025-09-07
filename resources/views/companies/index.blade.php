@extends('layouts.app')

@section('content')

    <x-flash-success :message="session('success')" />
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            <h3>{{ __('Companies') }}</h3>
        </div>

        <div class="card-body">
            <div class="row align-items-center mb-3">
                <div class="col-lg-2">
                    <a href="{{ route('companies.create') }}" class="btn btn-block btn-success fw-semibold text-white">
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
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($companies as $company)
                        <tr>
                            <x-table-link route="companies.show" :param="$company" :content="$company->name" :limit="35" />
                            <td>{{ $company->country }}</td>
                            <td>{{ $company->city }}</td>
                            <td class="d-flex align-items-center">
                                <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-info fw-semibold text-white">
                                    Edit
                                </a>

                                <form action="{{ route('companies.destroy', $company) }}" method="POST" onsubmit="return confirm('Are you sure your want to delete this company? This process cannot be reverted.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger text-white fw-semibold ms-2">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection