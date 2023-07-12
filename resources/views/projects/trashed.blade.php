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
                            <tr>
                                <td><span title="{{ $project->title }}">{{ Str::limit($project->title, 35) }}</span></td>
                                <td>{{ $project->due_date->format('d M Y') }}</td>
                                <td>{{ lastUpdated($project->deleted_at) }}</td>
                                <td>
                                    @can('restore project')
                                        <form action="{{ route('projects.restore', $project) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="submit" class="btn btn-sm btn-info text-white fw-semibold text-capitalize" value="Restore">
                                        </form>
                                    @endcan
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