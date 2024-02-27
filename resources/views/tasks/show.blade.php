@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />
    <x-errors :errors="$errors" />

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
                    @if ($task->project->is_open_or_pending && auth()->user()->can('update task'))
                        <div class="justify-content-end me-2">
                            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-info fw-semibold text-white">
                                Edit task
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <div class="ms-2">
                    @if ($task->description)
                        <div class="mb-1">
                            <p>{!! nl2br($task->description) !!}</p>
                            <hr />
                        </div>
                    @endif
                    <div class="mb-1">
                        <h5>Task Details</h5>
                        <div class="row gx-1">
                            <div class="col-md-2"><span class="fw-bold">Project</div>
                            <div class="col-md-9">{{ $task->project->title }}</div>
                        </div>

                        <div class="row gx-1">
                            <div class="col-md-2"><span class="fw-bold">Author</div>
                            <div class="col-md-9">{{ $task->author->name }}</div>
                        </div>
                        
                        <div class="row gx-1">
                            <div class="col-md-2"><span class="fw-bold">Assigned to</div>
                            <div class="col-md-9">{{ $task->user->name ?? 'Unassigned' }}</div>
                        </div>

                        <div class="row gx-1">
                            <div class="col-md-2"><span class="fw-bold">Last updated</div>
                            <div class="col-md-9">{{ lastUpdated($task->updated_at) }}</div>
                        </div>

                        <div class="row gx-1">
                            <div class="col-md-2"><span class="fw-bold">Status</div>
                            <div class="col-md-9">{{ $task->status }}</div>
                        </div>

                        @if ($task->getMedia())
                        <div class="mb-2">
                            <div>
                                <span class="fw-bold">Image(s)</span>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-around">
                                    @foreach($task->getMedia('attachments') as $image)
                                        <div class="mx-2">
                                            <a href="{{ $image->getUrl() }}">
                                                <img src="{{ $image->getUrl('thumb') }}" alt="{{ $image->file_name }}" class="card-img-top">
                                            </a>
                                            <div class="text-center mt-2">
                                                <form action="{{ route('task-image.delete', [$task, $image]) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger text-white fw-semibold">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div> 
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection