<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAlertNotification;
use App\Notifications\ProjectWarningNotification;
use Illuminate\Console\Command;

class CheckProjectDueDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:due-date-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the due date of the project';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $alertCounter = 0;
        $warningCounter = 0;

        $this->info('Checking the due dates of all the active projects.');
        foreach ($this->activeProjects() as $project) {

            if ($project->due_date_warning && now()->isMonday()) {
                $warningCounter++;
                if ($project->manager) {
                    $user = User::find($project->manager_id);
                    $user->notify(new ProjectWarningNotification($project, $user));
                } else {
                    $admin = User::role(['Admin'])->first();
                    $admin->notify(new ProjectWarningNotification($project, $admin));
                }
            }

            if ($project->due_date_alert && now()->isWeekday()) {
                $alertCounter++;
                if ($project->manager) {
                    $manager = User::find($project->manager_id);
                    $manager->notify(new ProjectAlertNotification($project, $manager));
                } else {
                    $admins = User::role(['Admin'])->get();
                    $admins->each(function ($admin) use ($project) {
                        $admin->notify(new ProjectAlertNotification($project, $admin));
                    });
                }
            }
        }
        $this->info('Checked all projects:');
        $this->info('The due date of '.$alertCounter.' active projects are expiring within a week.');
        $this->info('The due date of '.$warningCounter.' active project are expiring within a month.');

    }

    private function activeProjects()
    {
        return Project::with('manager')
            ->whereIn('status', ['open', 'pending'])
            ->orderBy('due_date', 'asc')
            ->orderby('id', 'desc')
            ->get();
    }
}
