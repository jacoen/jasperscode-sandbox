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
                    <h2 class="card-title">{{ $project->title }}</h2>
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
                    <p>{!! nl2br($project->description) !!}</p>
                    <hr />
                </div>
                <div class="mb-1">
                    <h3>Project details</h3>
                </div>

                <div class="row gx-1">
                    <div class="col-2"><span class="fw-bold">Assigned to</span></div>
                    <div class="col-9"><span>{{ $project->manager->name ?? 'Not assigned'}}</span></div>
                </div>

                <div class="row gx-1">
                    <div class="col-2"><span class="fw-bold">Due date</span></div>
                    <div class="col-9"><span>{{ $project->due_date->format('d M Y') }}</span></div>
                </div>

                <div class="row gx-1">
                    <div class="col-2"><span class="fw-bold">Last updated</span></div>
                    <div class="col-9"><span>{{ $project->last_updated }}</span></div>
                </div>
                <hr>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4>Tasks</h4>
                    </div>
                    <div class="justify-content-end me-2">
                        <a href="{{ route('tasks.create', $project) }}" class="btn btn-block btn-success fw-semibold text-white">
                            New task
                        </a>
                    </div>
                </div>
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