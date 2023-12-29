<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        if ($user->hasRole(['Super Admin', 'Admin']) ) {
            $user->timestamps = false;
            $user->two_factor_enabled = true;
            $user->save();

            $user->timestamps = true;
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($user->hasRole(['Super Admin', 'Admin']) ) {
            $user->timestamps = false;
            $user->two_factor_enabled = true;
            $user->save();

            $user->timestamps = true;
        }
    }
}
