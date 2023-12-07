<?php

namespace App\Observers;

use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $task->project->touch();
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        $task->load('project');

        if (! $task->project->trashed()) {
            $task->project->touch();
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleting(Task $task): void
    {
        $task->status = 'closed';
        $task->user_id = null;
        $task->save();
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        $task->status = 'restored';
        $task->save();
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
