<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('project:due-date-check')->dailyAt('6:00');
        //$schedule->command('telescope:prune --hours=48')->daily();
        $schedule->command('project:check-expiration')->dailyAt('1:00');
        $schedule->command('project:expiration-report')->weeklyOn(1, '4:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
