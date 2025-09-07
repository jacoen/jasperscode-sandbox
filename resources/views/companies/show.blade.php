@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="card-title">
                    {{ $company->name }}
                </h2>
                <div>
                    <a href="{{ route('companies.edit', $company) }}" class="btn btn-info fw-semibold text-white">
                        Edit company
                    </a> 
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="mb-1">
                <h4>Company details</h4>
                <div>
                    <span class="fw-semibold">Country: </span>
                    {{ $company->country }}
                </div>

                <div>
                    <span class="fw-semibold">City: </span>
                    {{ $company->city }}
                </div>

                <div>
                    <span class="fw-semibold">Postcode: </span>
                    {{ $company->postal_code }}
                </div>

                <div>
                    <span class="fw-semibold">Address: </span>
                    {{ $company->address }}
                </div>

                <div>
                    <span class="fw-semibold">Phone number: </span>
                    {{ $company->phone }}
                </div>
            </div>
            <div class="mb-1">
                <h5>Contact details</h5>
                <div>
                    <span class="fw-semibold">contact name: </span>
                    {{ $company->contact_name }}
                </div>

                <div>
                    <span class="fw-semibold">email address: </span>
                    {{ $company->contact_email }}
                </div>
            </div>

            <hr />
            <div class="mb-1">
                <h5>Projects</h5>
                @if(! $projects->count())
                    <p>No projects yet</p>
                @else
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Title</th>
                                <th scope="col">Manager</th>
                                <th scope="col">Status</th>
                                <th scope="col">Due date</th>
                                <th scope="col">Last updated</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($projects as $project)
                                <tr>
                                    <x-table-link route="projects.show" :param="$project" :content="$project->title" :limit="35"/>
                                    <td>{{ $project->manager->name ?? 'Not assigned' }}</td>
                                    <td>{{ $project->status }}</td>
                                    <td>{{ $project->due_date->format('d M Y') }}</td>
                                    <td>{{ lastUpdated($project->updated_at) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{ $projects->links() }}
                @endif
            </div>
        </div>
    </div>
@endsection