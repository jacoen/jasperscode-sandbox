@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />

    <div class="card mb-4">
        <div class="card-header">
            <h3>{{ __('Projects') }}</h3>
        </div>

        <div class="card-body">
            @can('create project')
                <div class="row mb-3">
                    <div class="col-md-2">
                        <div class="d-flex justify-content-start">
                            <a href="{{ route('projects.create') }}" class="btn btn-block btn-success fw-semibold text-white">
                                Create project
                            </a>
                        </div>
                    </div>
                </div>
            @endcan

            @if(! $projects->count())
                <p class="ms-2">No projects yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Manager</th>
                            <th scope="col">Due date</th>
                            @if (auth()->user()->can('edit project') || auth()->user()->can('delete project'))
                                <th></th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($projects as $project)
                            <tr>
                                <td><a href="{{ route('projects.show', $project)}}" class="text-decoration-none text-reset fw-semibold">{{ $project->title }}</a></td>
                                <td>{{ $project->manager ? $project->manager->name : 'Not assigned' }}</td>
                                <td>{{ $project->due_date->format('d-m-Y') }}</td>
                                @if (auth()->user()->can('edit project') || auth()->user()->can('delete project'))
                                    <td class="d-flex align-items-center">
                                        @can('edit project')
                                            <a class="btn btn-sm btn-info fw-semibold text-white" href="{{ route('projects.edit', $project) }}">
                                                Edit
                                            </a>
                                        @endcan

                                        @can('delete project')
                                            <form action="{{ route ('projects.destroy', $project) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger text-white fw-semibold ms-2">
                                                    Delete
                                                </button>
                                            </form>    
                                        @endcan
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection