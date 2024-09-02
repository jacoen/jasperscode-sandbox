<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Console\Command;

class CoupleProjectsAndCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:add_companies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start adding a default company to all projects that do not belong to a company.');
        $progressbar = $this->output->createProgressBar($this->incompleteProjectsCount());
        $progressbar->start();

        foreach ($this->incompleteProjects() as $project) {
            $project->timestamps = false;
            $project->update([
                'company_id' => $this->firstCompany()->id,
            ]);
            $project->timestamps = true;
            $progressbar->advance();
        }

        $this->info('');
        $this->info('Finished adding couping companies and projects without a company.');
    }



    private function incompleteProjectsCount()
    {
        return Project::whereNull('company_id')
            ->whereNotIn('status', ['closed', 'expired'])
            ->count();
    }

    private function incompleteProjects()
    {
        return Project::whereNull('company_id')
            ->whereNotIn('status', ['closed', 'expired'])
            ->get();
    }

    private function firstCompany()
    {
        return Company::first();
    }
}
