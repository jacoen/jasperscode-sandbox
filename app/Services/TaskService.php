<?php

namespace App\Services;

use App\Exceptions\CreateTaskException;
use App\Exceptions\InvalidProjectStatusException;
use App\Exceptions\ProjectDeletedException;
use App\Exceptions\UpdateTaskException;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    public function listTasks(string $search = null, string $status = null, int $userId = null): LengthAwarePaginator
    {
        $tasks = Task::with('project.manager', 'author', 'user')
            ->search($search)
            ->taskStatusFilter($status)
            ->filterByUser($userId)
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return $tasks;
    }

    public function storeTask(Project $project, array $validData, array $attachments = null): Task
    {
        if (! $project->is_open_or_pending) {
            throw new CreateTaskException('Cannot create a task for an inactive project.', $project);
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
            throw new UpdateTaskException('Cannot update the task because the project is inactive.', $task);
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

    public function trashedTasks(): LengthAwarePaginator
    {
        return Task::onlyTrashed()
            ->with('project', 'author', 'user')
            ->latest('deleted_at')
            ->orderBy('id', 'desc')
            ->paginate();
    }

    public function restoreTask(Task $task): Task
    {
        $project = $task->project;

        if ($project->trashed()) {
            throw new ProjectDeletedException('Could not restore task because the project has been trashed.');
        }

        if (! $project->is_open_or_pending) {
            throw new InvalidProjectStatusException('Could not restore task because the related project is inactive');
        }

        $task->restore();

        return $task;
    }

    public function findTasksByProject(Project $project, string $search = null, string $status = null): LengthAwarePaginator
    {
        return $project->tasks()
            ->search($search)
            ->taskStatusFilter($status)
            ->with('author', 'user')
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate(15);
    }
}
