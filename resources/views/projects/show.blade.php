@extends('layouts.app')

@section('content')
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
                <div class="justify-content-end me-2">
                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-info fw-semibold text-white">
                        Edit project
                    </a>
                </div>
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
                    <div class="col-9"><span>{{ $project->manager ? $project->manager->name : 'Not assigned'}}</span></div>
                </div>
                <div class="row gx-1">
                    <div class="col-1"><span class="fw-bold">Due date</span></div>
                    <div class="col-9"><span>{{ $project->due_date->format('d M Y') }}</span></div>
                </div>
                <hr>
                <h5>Tasks</h5>
                <p>No tasks yet.</p>
            </div>
        </div>
    </div>
@endsection