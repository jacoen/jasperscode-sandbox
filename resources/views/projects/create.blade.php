@extends('layouts.app')

@section('content')
    <x-errors :errors="$errors" />

    <div class="card mb-4">
        <div class="card-header">
            Create new project
        </div>

        <div class="card-body">
            <form action="{{ route('projects.store') }}" method="POST" class="px-4">
                @csrf
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="Project title" value="{{ old('title') }}" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}
                    </textarea>
                </div>

                <div class="row gx-3 mb-3">
                    <div class="col-md-6">
                        <label for="due_date" class="form-label">Due date</label>
                        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror">
                    </div>

                    <div class="col-md-6">
                        <label for="manager_id" class="form-label">Project manager</label>
                        <select class="form-select @error('manager_id') is-invalid @enderror" id="manager_id" name="manager_id">
                            <option value="" selected>Select a project manager</option>
                            @foreach ($managers as $id => $name)
                                <option value="{{ $id }}" {{ old('manager_id') == $id ? 'checked' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn btn-block btn-primary fw-semibold text-white">Submit</button>
                </div>
            </form>
        </div>
    </div>
@endsection