<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectWarningNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        $projectWarningCounter = 0;
        $projectAlertCounter = 0;

        $days = [
            Carbon::MONDAY,
            Carbon::WEDNESDAY,
            Carbon::FRIDAY,
        ];

        $this->info('The following project are about to expire :');
        foreach($this->activeProjects() as $project) {
            if ($project->due_date_warning && in_array(now()->dayOfWeek, $days)) {
                $projectWarningCounter++;

                if ($project->manager) {
                    $user = User::find($project->manager_id); 
                    $user->notify(new ProjectWarningNotification($project, $user));
                } else {
                    $admins = User::role(['Admin'])->get();
                    $admins->each(function ($admin) use ($project) {
                        $admin->notify(new ProjectWarningNotification($project, $admin));
                    });
                }
            }

            if($project->due_date_alert) {
                Log::warning('Alert: The project'.$project->title.' is due '. $project->due_date_difference);
                $projectAlertCounter++;
            }

            $this->info('project due date: '. $project->due_date->format('Y-m-d').' - status: '. $project->status .' - title: '.$project->title);
        }
        $this->info($projectWarningCounter.' projects are less than a month away from the due date.');
        $this->info($projectAlertCounter.' projects are less than a week away from the due date.');
        return;
    }

    private function activeProjects()
    {
        return Project::with('manager')
            // ->where('due_date', '>=', now())
            ->whereIn('status', ['open', 'pending'])
            ->orderBy('due_date', 'asc')
            ->orderby('id', 'desc')
            ->get();
    }
}
