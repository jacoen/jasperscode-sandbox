<?php

namespace App\Services;

use App\Events\RoleUpdatedEvent;
use App\Exceptions\InvalidEmailException;
use App\Exceptions\UnableToChangeRoleException;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function store(array $validData): User
    {
        $user = User::make($validData);
        $user->password = Hash::make(Str::password(64));
        $user->save();

        $user->generatePasswordToken();
        $user->syncRoles($validData['role'] ?? 'User');
        event(New RoleUpdatedEvent($user));

        $user->notify(new AccountCreatedNotification());

        return $user;
    }

    public function update(User $user, array $validData): User
    {
        $oldRole = $user->roles->first()->id;

        if ($validData['email'] !== $user->email) {
            throw new InvalidEmailException('The email does not match the original email address');
        }

        if ($user->hasRole('Super Admin') && (int)$user->roles()->first()->id !== (int)$validData['role']) {
            throw new UnableToChangeRoleException('Not able to change the role of this user');
        }

        $role = $validData['role'];
        unset($validData['role']);

        $user->update($validData);

        if ($role !== $oldRole) {
            $user->syncRoles($role);
            event(new RoleUpdatedEvent($user));
        }

        return $user;
    }

    public function getUsersByRoles(array $roles): Collection
    {
        return User::role($roles)->pluck('name', 'id');
    }
}