@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />

    <div>
        <a class="btn btn-link" href="{{ route('projects.show', $task->project) }}">
            &#xab; Return to project
        </a>

        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">{{ $task->title }}</h3>
                    </div>
                    @can('update task')
                        <div class="justify-content-end me-2">
                            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-info fw-semibold text-white">
                                Edit task
                            </a>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="card-body">
                <div class="ms-2">
                    <div class="mb-1">
                        <p>{!! nl2br($task->description) !!}</p>
                        <hr />
                    </div>
                    <div class="mb-1">
                        <h5>Task Details</h5>
                        <div class="row gx-1">
                            <div class="col-md-1"><span class="fw-bold">Project</div>
                            <div class="col-md-9">{{ $task->project->title }}</div>
                        </div>

                        <div class="row gx-1">
                            <div class="col-md-1"><span class="fw-bold">Author</div>
                            <div class="col-md-9">{{ $task->author->name }}</div>
                        </div>
                        
                        <div class="row gx-1">
                            <div class="col-md-1"><span class="fw-bold">Assigned to</div>
                            <div class="col-md-9">{{ $task->user->name ?? 'Unassigned' }}</div>
                        </div>

                        {{-- <div class="row gx-1">
                            <div class="col-md-1"><span class="fw-bold"></div>
                            <div class="col-md-9">{{ $task }}</div>
                        </div> --}}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection