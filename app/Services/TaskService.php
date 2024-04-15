<?php

namespace App\Services;

use App\Exceptions\ActiveTaskForceDeleteException;
use App\Exceptions\InvalidProjectStatusException;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    public function listTasks($search = null, $status = null, $userId = null): LengthAwarePaginator
    {
        return Task::with('project.manager', 'author', 'user')
            ->when($search, function($query) use ($search) {
                $query->where('title', 'LIKE', '%'.$search.'%');
            })
            ->when($status, function($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();
    }

    public function storeTask(Project $project, array $validData, array $attachments = null): Task
    {
        if (! $project->is_open_or_pending) {
            throw new InvalidProjectStatusException('Could create a task when the status of the project is not open or pending.');
        }

        $task = $project->tasks()->create($validData);

        if ($attachments) {
            foreach ($attachments as $attachment) {
                $task->addMedia($attachment)
                    ->usingName($task->title)
                    ->toMediaCollection('attachments');
            }
        }

        if (isset($validData['user_id'])) {
            User::find($validData['user_id'])->notify(new TaskAssignedNotification($task));
        }

        return $task;
    }

    public function updateTask(Task $task, array $validData, array $attachments = null): Task
    {
        if (! $task->project->is_open_or_pending) {
            throw new InvalidProjectStatusException('Could not update the task because the project is inactive.');
        }

        $task->update($validData);

        if ($attachments) {
            $task->clearMediaCollection('attachments');
            foreach ($attachments as $attachment) {
                $task->addMedia($attachment)
                    ->usingName($task->title)
                    ->toMediaCollection('attachments');
            }
        }

        if (isset($validData['user_id']) && $task->wasChanged('user_id')) {
            User::find($validData['user_id'])->notify(new TaskAssignedNotification($task));
        }

        return $task;
    }

    public function destroyTask(Task $task): void
    {
        $task->delete();
    }

    public function trashedTasks(): LengthAwarePaginator
    {
        return Task::onlyTrashed()
        ->with('project')
        ->latest('deleted_at')
        ->orderBy('id', 'desc')
        ->paginate();

    }

    public function restoreTask(Task $task): Task
    {
        $project = $task->project;

        if ($project->trashed()) {
            throw new InvalidProjectStatusException('Could not restore task because the project has been trashed.');
        }
        
        if (! $project->is_open_or_pending) {
            throw new InvalidProjectStatusException('Could not restore task because the related project is inactive');
        }

        $task->restore();

        return $task;
    }

    public function forceDelete(Task $task): void
    {
        if (! $task->trashed()) {
            throw new ActiveTaskForceDeleteException('Cannot force delete this task.');
        }

        $task->forceDelete();
    }

    public function findTasksByProject($project, $search = null, $status = null): LengthAwarePaginator
    {
        return $project->tasks()
            ->when($search, function ($query) use($search) {
                $query->where('title', 'LIKE', '%'.$search.'%');
            })
            ->when($status, function ($query) use($status) {
                $query->where('status', $status);
            })
            ->with('author', 'user')
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate(15);
    }
}