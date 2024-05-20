<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectExpirationNotification;
use Illuminate\Console\Command;

class ProjectExpirationCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all the active project on expiration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = 0;

        foreach ($this->ActiveProjects() as $project) {
            $pinnedProject = $project->is_pinned ? false : $project->is_pinned;

            $project->timestamps = false;
            $project->update([
                $project->status = 'expired',
                $project->is_pinned = $pinnedProject,
            ]);
            $project->timestamp = true;

            if ($project->manager) {
                $user = User::find($project->manager_id);
                $this->NotifyManagerExpiredProject($user, $project);
            }
            $count++;
        }

        $this->info('The check has been completed. '.$count.' projects have expired. The status of these projects has been changed to expired.');
    }

    private function ActiveProjects()
    {
        $projects = Project::with('manager')
            ->whereIn('status', ['open', 'pending', 'restored'])
            ->where('due_date', '<', now())
            ->get();

        return $projects;
    }

    private function NotifyManagerExpiredProject(User $user, Project $project)
    {
        $user->notify(new ProjectExpirationNotification($project));
    }
}
