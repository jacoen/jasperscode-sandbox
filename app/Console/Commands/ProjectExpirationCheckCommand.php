<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
            $project->timestamps = false;
            $project->update(['status' => 'expired']);
            $project->timestamp = true;

            $count++;
        }

        foreach ($this->expiredPinnedProjects() as $project) {
            $project->timestamps = false;
            $project->update([
                'status' => 'expired',
                'is_pinned' => false,
            ]);
            $project->timestamp = true;

            $count++;
        }
        
        Log::info($count.' projects have expired, the status has been changed to expired.');
        $this->info('The check has been completed. Please check the log for the results');
        return;
    }

    private function ActiveProjects()
    {
        $projects = Project::whereIn('status', ['open', 'pending', 'restored'])
            ->where('due_date', '<', now())
            ->where('is_pinned', false)
            ->get();

        return $projects;
    }

    private function expiredPinnedProjects()
    {
        $projects = Project::whereIn('status', ['open', 'pending', 'restored'])
            ->where('due_date', '<', now())
            ->where('is_pinned', true)
            ->get();

        return $projects;
    }
}
