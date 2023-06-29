@extends('layouts.app')

@section('content')
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            Create a new task
        </div>

        <div class="card-body">
            <form action="{{ route('tasks.store', $project) }}" method="POST" class="px-4">
                @csrf
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="The title of the task" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" 
                        class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="row gx-3 mb-3">
                    <div class="col-md-6">
                        <label for="user_id" class="form-label">Assigned to</label>
                        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                            <option value="" selected>Assign to employee</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" {{ old('user_id') == $id ? 'selected' : ''}}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn btn-success fw-semibold text-white">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection