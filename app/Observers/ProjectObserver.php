<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
    /**
     * Handle the Project "creating" event.
     */
    public function creating(Project $project): void
    {
        if (! $project->status) {
            $project->status = config('definitions.statuses.Open');
        }
    }

    public function deleting(Project $project)
    {
        if (request()->routeIs('projects.destroy')) {
            $project->tasks()->each(function ($task) {
                $task->delete();
            });

            $project->timestamps = false;
            $project->status = 'closed';
            $project->manager_id = null;
            $project->save();
        }
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        // 
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

    /**
     * Handle the Project "force deleted" event.
     */
    public function forceDeleting(Project $project): void
    {
        $project->tasks()->withTrashed()->each(function ($task) {
            $task->forceDelete();
        });
    }
}
