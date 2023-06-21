@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />

    <div>
        <a class="btn btn-link" href="{{ route('projects.index') }}">
            &#xab; Return to project overview
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title">{{ $project->title }}</h3>
                </div>
                @can('update project')
                    <div class="justify-content-end me-2">
                        <a href="{{ route('projects.edit', $project) }}" class="btn btn-info fw-semibold text-white">
                            Edit project
                        </a>
                    </div>
                @endcan
            </div>
        </div>

        <div class="card-body">
            <div class="ms-2">
                <div class="mb-1">
                    <p>{{ $project->description }}</p>
                    <hr />
                </div>
                <div class="mb-1">
                    <h5>Project details</h5>
                </div>
                <div class="row gx-1">
                    <div class="col-1"><span class="fw-bold">Assigned to</span></div>
                    <div class="col-9"><span>{{ $project->manager->name ?? 'Not assigned'}}</span></div>
                </div>
                <div class="row gx-1">
                    <div class="col-1"><span class="fw-bold">Due date</span></div>
                    <div class="col-9"><span>{{ $project->due_date->format('d M Y') }}</span></div>
                </div>
                <hr>
                <h5>Tasks</h5>
                @if (! $project->tasks->count())
                    <p>No tasks in this project yet.</p>
                @else
                    <table class="table px-2">
                        <thead>
                            <tr>
                                <th scope="col">Title</th>
                                <th scope="col">Author</th>
                                <th scope="col">Assigned to</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks as $task)
                                <tr>
                                    <td><a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-reset fw-semibold">{{ $task->title }}</a></td>
                                    <td>{{ $task->author->name }}</td>
                                    <td>{{ $task->user->name ?? 'Unassigned' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection