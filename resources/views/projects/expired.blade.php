@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            <h3>{{ __('Expired projects') }}</h3>
        </div>

        <div class="card-body">
            @if(! $projects->count())
                No expired projects
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Manager</th>
                            <th scope="col">Status</th>
                            <th scope="col">Due Date</th>
                            @if (auth()->user()->can('update project') || auth()->user()->can('delete project'))
                                <th></th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($projects as $project)
                            <tr>
                                <td class="fw-bold">{{ Str::limit($project->title, 55) }}</td>
                                <td>{{ $project->manager->name ?? 'Not assigned' }}</td>
                                <td>{{ $project->status }}</td>
                                <td>{{ $project->due_date->format('d M Y') }}</td>
                                @if (auth()->user()->can('update project') || auth()->user()->can('delete project'))
                                    <td class="d-flex align-items-center">
                                        @can('update project')
                                            <a class="btn btn-sm btn-info fw-semibold text-white" href="{{ route('projects.edit', $project) }}">
                                                Edit
                                            </a>
                                        @endcan

                                        @can('delete project')
                                            <form action="{{ route ('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Are you sure your want to delete this project?');">
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

                <x-pagination :records="$projects" />
            @endif
        </div>
    </div>
@endsection