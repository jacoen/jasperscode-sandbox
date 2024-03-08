<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    public function listTasks($search = null, $status = null, $userId = null): LengthAwarePaginator
    {
        return Task::with('project', 'author', 'user')
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
            throw new \Exception('Cannot create a task when the project is not open or pending.');
        }

        $data = array_merge($validData, ['author_id' => auth()->id()]);

        $task = $project->tasks()->create($data);

        if ($attachments) {
            foreach ($attachments as $attachment) {
                $task->addMedia($attachment)
                    ->usingName($task->title)
                    ->toMediaCollection('attachments');
            }
        }

        if (isset($data['user_id'])) {
            User::find($data['user_id'])->notify(new TaskAssignedNotification($task));
        }

        return $task;
    }

    public function updateTask(Task $task, array $validData, array $attachments = null)
    {
        if (! $task->project->is_open_or_pending) {
            throw new \Exception('Could not update the task because the project is inactive.');
        }

        $task->update($validData);

        if ($attachments) {
            $task->clearMediaCollection();
            foreach ($attachments as $attachment) {
                $task->addMedia($attachment)
                    ->usingName($task->title)
                    ->toMediaCollection('attachments');
            }
        }

        if (isset($validData['user_id']) && $task->wasChanged('user_id')) {
            User::find($validData['user_id'])->notify(new TaskAssignedNotification($task));
        }

        if ($task->wasChanged('title')) {
            $task = $task->fresh();
        }

        return $task;
    }

    public function trashedTasks(): LengthAwarePaginator
    {
        return Task::onlyTrashed()
        ->with('project')
        ->latest('deleted_at')
        ->orderBy('id', 'desc')
        ->paginate();

    }

    public function restoreTask(Task $task)
    {
        if ($task->project->trashed()) {
            throw new \Exception('Could not restore task because the project has been trashed.');
        }
        
        if ($task->project->status == 'closed' || $task->project->status == 'completed' || $task->project->status == 'expired') {
            throw new \Exception('Could not restore task because the related project is inactive');
        }

        $task->restore();

        return $task;
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