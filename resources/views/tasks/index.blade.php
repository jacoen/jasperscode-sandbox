@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />

    <div class="card mb-4">
        <div class="card-header">
            Tasks
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-2">
                    <div class="justify-content-start">
                        <a href="{{ route('tasks.create') }}" class="btn btn-block btn-success fw-semibold text-white">
                            Create task
                        </a>
                    </div>
                </div>
            </div>

            @if(! $tasks->count())
                <p class="mb-2">No tasks yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">project</th>
                            <th scope="col">Author</th>
                            <th scope="col">Assigned to</th>
                            <td scope="col"></td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr>
                                <td><a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-reset fw-semibold">{{ $task->title }}</a></td>
                                <td><a href="{{ route('projects.show', $task->project) }}" class="text-decoration-none text-reset fw-semibold">{{ $task->project->title }}</a></td>
                                <td>{{ $task->author->name }}</td>
                                <td>{{ $task->user->name ?? 'Unassigned' }}</td>
                                <td class="d-flex align-items-center">
                                    <a class="btn btn-sm btn-info fw-semibold text-white" href="{{ route('tasks.edit', $task) }}">
                                        Edit
                                    </a>

                                    <form action="{{ route('tasks.destroy', $task) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger text-white fw-semibold ms-2">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <x-pagination :records="$tasks" />
            @endif
        </div>
    </div>
@endsection