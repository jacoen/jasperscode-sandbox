<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectExpirationReportNotification;
use Illuminate\Console\Command;

class ProjectExpirationReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:expiration-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a message to the admin(s) with a report of the expired projects last week.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::role(['Admin'])->get();
        $projects = $this->countExpiredProjectsLastWeek();

        if ($projects >= 1) {
            $yearWeek = $this->generateYearCode();
            foreach ($users as $user)
            {
                $user->notify(new ProjectExpirationReportNotification($projects, $user, $yearWeek));
            }

            $this->info('The code this week is: '.$yearWeek);
        }
    }

    private function countExpiredProjectsLastWeek(): int
    {
        return Project::whereBetween('due_date', [now()->subWeek(), now()])
            ->whereNotIn('status', ['closed', 'completed', 'deleted'])
            ->count();
    }

    private function generateYearCode()
    {
        $year = now()->year;
        $week = now()->subWeek()->weekOfYear;

        return $year.'-'.$week;
    }
}
