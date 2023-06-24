@extends('layouts.app')

@section('content')
    <x-flash-success :message="session('success')" />

    <div class="card mb-4">
        <div class="card-header">
            {{ __('Users') }}
        </div>

        <div class="card-body">
            <div class="mb-3">
                <div class="row">
                    <div class="col-md-2">
                        <div class="d-flex justify-content-start ms-2">
                            <a href="{{ route('users.create') }}" class="btn btn-success fw-semibold text-white">
                                Create user
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <table class="table">
                <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roles()->first()->name }}</td>
                        <td class="d-flex align-items-center">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-info text-white fw-semibold">
                                Edit
                            </a>

                            <form action="{{ route('users.destroy', $user) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm text-white btn-danger fw-semibold ms-2 text-capitalize">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            <div class="pagination justify-content-center mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
