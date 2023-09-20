@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            <h4>Trashed tasks</h4>
        </div>

        <div class="card-body">
            @if(! $tasks->count())
                <p class="mb-2">No tasks yet.</p>
            @else
                <table class="table table-responsive">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Project</th>
                            <th scope="col">Deleted</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr class="align-baseline">
                                <td><span title="{{ $task->title }}">{{ Str::limit($task->title, 30) }}</span></td>
                                <td><span title="{{ $task->project->title}}">{{ Str::limit($task->project->title, 30) }}</span></td>
                                <td>{{ lastUpdated($task->deleted_at) }}</td>
                                <td class="d-flex align-items-center">
                                    <form action="{{ route('tasks.restore', $task) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-info text-white fw-semibold">Restore</button>
                                    </form>

                                    @if (auth()->user()->can('delete task') && auth()->user()->hasAnyRole(['Admin', 'Super Admin']))
                                        <form action="{{ route('tasks.delete', $task) }}" method="POST" onsubmit="return confirm('Are you sure you want to force delete this task? This process cannot be reversed!')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="submit" class="btn btn-sm btn-danger text-white fw-semibold ms-2 text-capitalize" value="Force Delete">
                                        </form>
                                    @endif
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