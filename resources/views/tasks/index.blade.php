@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />

    <div class="card mb-4">
        <div class="card-header fw-bold">
            @hasanyrole('Admin|Super Admin')
                Tasks
            @else
                {{ auth()->user()->name.'\'s tasks'}}
            @endhasanyrole
        </div>

        <div class="card-body">

            @if(! $tasks->count())
                <p class="mb-2">No tasks yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Project</th>
                            <th scope="col">Author</th>
                            @hasanyrole('Admin|Super Admin')
                                <th scope="col">Assigned to</th>
                            @endhasanyrole
                            <th scope="col">Last updated</th>
                            @if (auth()->user()->can('update task') || auth()->user()->can('delete task'))
                                <td scope="col"></td>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr>
                                <td><a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-reset fw-semibold"><span title="{{ $task->title }}"> {{ Str::limit($task->title, 25) }} </span></a></td>
                                <td><a href="{{ route('projects.show', $task->project) }}" class="text-decoration-none text-reset fw-semibold">{{ Str::limit($task->project->title, 25) }}</a></td>
                                <td>{{ $task->author->name }}</td>
                                @hasanyrole('Admin|Super Admin')
                                    <td>{{ $task->user->name ?? 'Unassigned' }}</td>
                                @endhasanyrole
                                <td>{{ lastUpdated($task->updated_at) }}</td>
                                @if (auth()->user()->can('update task') || auth()->user()->can('delete task'))
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
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <x-pagination :records="$tasks" />
            @endif
        </div>
    </div>
@endsection