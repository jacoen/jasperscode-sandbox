@extends('layouts.app')

@section('content')
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">{{ $project->title }}</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('projects.update', $project) }}" method="POST" class="px-4">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="Project title" value="{{ old('title', $project->title) }}" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-control @error('description') is-invalid @enderror" 
                        rows="3">{{ old('description', $project->description) }}</textarea>
                </div>

                <div class="row gx-3 mb-3">
                    <div class="col-md-6">
                        <label for="due_date" class="form-label">Due date</label>
                        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $project->due_date->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label for="manager_id" class="form-label">Project manager</label>
                        <select class="form-select @error('manager_id') is-invalid @enderror" id="manager_id" name="manager_id">
                            <option value="" selected>Select a project manager</option>
                            @foreach ($managers as $id => $name)
                                <option value="{{ $id }}" {{ old('manager_id', $project->manager_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row justify-content-between">
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary fw-semibold text-white">
                            Update
                        </button>
                    </div>
                    <div class="col-md-9"></div>
                    <div class="col-md-1">
                        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-info fw-semibold">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection