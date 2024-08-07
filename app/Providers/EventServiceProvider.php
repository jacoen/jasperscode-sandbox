<?php

namespace App\Providers;

use App\Events\RoleUpdatedEvent;
use App\Listeners\RoleUpdatedListener;
use App\Models\Project;
use App\Models\Task;
use App\Observers\ProjectObserver;
use App\Observers\TaskObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        RoleUpdatedEvent::class => [
            RoleUpdatedListener::class,
        ],
    ];

    /**
     * The model observers for your application
     *
     * @var array
     */
    protected $observers = [
        Task::class => [TaskObserver::class],
        Project::class => [ProjectObserver::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
