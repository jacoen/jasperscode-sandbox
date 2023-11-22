<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
    public function deleting(Project $project)
    {
        if (! $project->isForceDeleting()) 
        {
            $project->tasks()->each(function ($task) {
                $task->delete();
            });
        }
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        if (! $project->isForceDeleting()) 
        {
            $project->timestamps = false;
            $project->status = 'closed';
            $project->manager_id = null;
            $project->save();
        }
    }

    /**
     * Handle the Project "restoring" event.
     */
    public function restoring(Project $project): void
    {
        $deleted = $project->deleted_at;

        $project->status = 'restored';
        $project->save();

        $project->tasks()->onlyTrashed()->each(function ($task) use ($deleted) {
            if ($task->deleted_at->gte($deleted)) {
                $task->restore();
            }
        });
    }
}
