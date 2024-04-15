<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;

use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
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
            $roles = Role::whereIn('name', ['manager', 'employee', 'user'])->orderBy('id', 'asc')->get(['id', 'name']);
        }

        return view('users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->store($request->validated());

        return redirect()->route('users.index')
            ->with('success', 'A new user was created.');
    }

    public function edit(User $user)
    {
        if (auth()->user()->hasRole('Super Admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::whereIn('name', ['Manager', 'Employee', 'User'])->orderBy('id', 'asc')->get(['id', 'name']);
        }

        $userRole = $user->roles->pluck('id')->first();

        return view('users.edit', compact(['user', 'roles', 'userRole']));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $this->userService->update($user, $request->validated());

            return redirect()->route('users.index')
                ->with('success', $user->name.'\'s account has been updated!');
        } catch(\Exception $e) {
            return redirect()->route('users.edit', $user)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User has been deleted!');
    }
}
