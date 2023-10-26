@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            <h3>{{ __('Thrashed projects') }}</h3>
        </div>

        <div class="card-body">
            @if(! $projects->count())
                <p class="ms-2">No trashed projects yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Due date</th>
                            <th scope="col">Deleted at</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($projects as $project)
                            <tr class="align-baseline">
                                <td><span class="fw-semibold" title="{{ $project->title }}">{{ Str::limit($project->title, 35) }}</span></td>
                                <td>{{ $project->due_date->format('d M Y') }}</td>
                                <td>{{ lastUpdated($project->deleted_at) }}</td>
                                <td class="d-flex align-items-center">
                                    @can('restore project')
                                        <form action="{{ route('projects.restore', $project) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-info text-white fw-semibold">Restore</button>
                                        </form>
                                    @endcan

                                    @if (auth()->user()->can('delete project') && auth()->user()->hasRole('Admin'))    
                                        <form action="{{ route('projects.delete', $project) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this project? This project cannot be reversed!')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-danger text-white fw-semibold ms-2">
                                                Force Delete
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            <x-pagination :records="$projects" />
            @endif
        </div>
    </div>
@endsection