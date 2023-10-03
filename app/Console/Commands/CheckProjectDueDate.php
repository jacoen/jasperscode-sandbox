<?php

namespace App\Console\Commands;

use App\Models\Project;
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
        $this->info('The following project are about to expire :');
        foreach(Project::all() as $project) {
            if ($project->due_date_warning || $project->due_date_alert) {
                Log::info('The due date of '.$project->title.' is '.$project->due_date_difference);
                $this->info($project->title.', due date: '.$project->due_date->format('Y-m-d'));
            }
        }
        return;
    }
}
