<?php

namespace App\Listeners;

use App\Events\RoleUpdatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RoleUpdatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RoleUpdatedEvent $event): void
    {
        if ($event->user->hasRole(['Admin', 'Super Admin'])) {
            $event->user->timestamps = false;
            $event->user->two_factor_enabled = 1;
            $event->user->save();

            $event->user->timestamps = true;
        }
    }
}
