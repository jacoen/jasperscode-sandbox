<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Notifications\AccountCreatedNotification;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(): View
    {
        $users = User::paginate();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        if (auth()->user()->hasRole('Super Admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::whereIn('name', ['employee', 'user'])->get(['id', 'name']);
        }

        return view('users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::make($request->validated());
        $user->password = Hash::make(Str::password(64));
        $user->save();

        $user->generatePasswordToken();
        $user->assignRole($request->role);
        $user->notify(new AccountCreatedNotification());


        return redirect()->route('users.index')
            ->with('success', 'New user was created!');
    }

    public function edit(User $user)
    {
        if (auth()->user()->hasRole('Super Admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::whereIn('name', ['employee', 'user'])->get(['id', 'name']);
        }

        return view('users.edit', compact(['user', 'roles']));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        $user->syncRoles($request->role);

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
