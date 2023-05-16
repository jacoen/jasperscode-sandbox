<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::paginate();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::make($request->validated());
        $user->password = Hash::make(Str::password(64));
        $user->save();

        return redirect()->route('users.index')
            ->with('success', 'New user was created!');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return redirect()->route('users.index')
            ->with('success', $user->name.'\'s account has been updated!');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User has been deleted!');
    }
}
