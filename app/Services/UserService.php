<?php

namespace App\Services;

use App\Events\RoleUpdatedEvent;
use App\Exceptions\InvalidEmailException;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserService
{
    public function store(array $validData)
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

    public function update(User $user, array $validData, array $roleData)
    {
        $oldRole = $user->roles->first()->id;

        if ($validData['email'] !== $user->email) {
            throw new InvalidEmailException('The email does not match the original email address');
        }

        if ($user->hasRole('Super Admin') && $roleData['role'] != Role::where('name', 'Super Admin')->value('id')) {
            throw new \Exception('Not able to change the role of this user');
        }

        $user->update($validData);

        if ($roleData['role'] !== $oldRole) {
            $user->syncRoles($roleData['role']);
            event(new RoleUpdatedEvent($user));
        }

        return $user->fresh();
    }
}