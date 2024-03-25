<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * @see app\Observers\TaskObserver for the model events
     */
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(): View
    {
        $route = Route::currentRouteName();
        $tasks = Task::with('project', 'author', 'user')
            ->where('user_id', auth()->id())
            ->when(request()->search, function ($query) {
                $query->where('title', 'LIKE', '%'.request()->search.'%');
            })
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('tasks.index', compact('tasks', 'route'));
    }

    public function create(Project $project): View|RedirectResponse
    {
        if (! $project->is_open_or_pending) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['error' => 'Cannot create a task when the project is not open or pending.']);
        }

        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');

        return view('tasks.create', compact(['employees', 'project']));
    }

    public function store(Project $project, StoreTaskRequest $request): RedirectResponse
    {
        if (! $project->is_open_or_pending) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['error' => 'Cannot create a task when the project is not open or pending.']);
        }

        $data = Arr::add($request->validated(), 'author_id', auth()->id());
        $task = $project->tasks()->create($data);

        if ($attachments = $request->file('attachments')) {
            foreach ($attachments as $attachment) {
                $task->addMedia($attachment)
                    ->usingName($task->title)
                    ->toMediaCollection('attachments');
            }
        }

        if (isset($request->user_id)) {
            User::find($request->user_id)->notify(new TaskAssignedNotification($task));
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'A new task has been created.');
    }

    public function show(Task $task): View
    {
        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task): View|RedirectResponse
    {
        if (! $task->project->is_open_or_pending) {
            return redirect()->route('projects.show', $task->project)
                ->withErrors([
                    'error' => 'Could not update the task because the project is inactive.',
                ]);
        }

        $task->load('project');

        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');
        $statuses = Arr::add(config('definitions.statuses'), 'Restored', 'restored');

        return view('tasks.edit', compact(['task', 'employees', 'statuses']));
    }

    public function update(Task $task, UpdateTaskRequest $request): RedirectResponse
    {
        if (! $task->project->is_open_or_pending) {
            return redirect()->route('projects.show', $task->project)
                ->withErrors([
                    'error' => 'Could not update the task because the project is inactive.',
                ]);
        }

        $task->update($request->validated());

        if ($attachments = $request->file('attachments')) {
            $task->clearMediaCollection();
            foreach ($attachments as $attachment) {
                $task->addMedia($attachment)
                    ->usingName($task->title)
                    ->toMediaCollection('attachments');
            }
        }

        if (isset($request->user_id) && $task->wasChanged('user_id')) {
            User::find($request->user_id)->notify(new TaskAssignedNotification($task));
        }

        if ($task->wasChanged('title')) {
            $task = $task->fresh();
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'The task '.$task->title.' has been updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $project = $task->project_id;

        $taskTitle = $task->title;

        $task->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'The task '.$taskTitle.' has been deleted.');
    }

    public function trashed(): View
    {
        $this->authorize('restore task', Task::class);

        $tasks = Task::onlyTrashed()
            ->with('project')
            ->latest('deleted_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('tasks.trashed', compact('tasks'));
    }

    public function restore(Task $task): RedirectResponse
    {
        $this->authorize('restore', $task);

        if ($task->project->trashed()) {
            return redirect()->route('tasks.trashed')
                ->withErrors(['error' => 'Could not restore task because the project has been deleted.']);
        }

        if ($task->project->status == 'closed' || $task->project->status == 'completed' || $task->project->status == 'expired') {
            return redirect()
                ->route('tasks.trashed')->withErrors(['error' => 'Could not restore task becaues the project is either closed, completed or expired.']);
        }

        $task->restore();

        return redirect()->route('tasks.trashed')
            ->with('success', 'The task '.$task->title.'has been restored.');
    }

    public function forceDelete(Task $task): RedirectResponse
    {
        $this->authorize('forceDelete', $task);

        $task->forceDelete();

        return redirect()->route('tasks.trashed')
            ->with('success', 'The task has been permanently deleted.');
    }

    public function adminTasks(): View
    {
        abort_if(! auth()->user()->hasRole('Admin'), 403);

        $route = Route::currentRouteName();
        $tasks = Task::with('project', 'author', 'user')
            ->when(request()->search, function ($query) {
                $query->where('title', 'LIKE', '%'.request()->search.'%');
            })
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->latest('updated_at')
            ->orderByDesc('id')
            ->paginate();

        return view('tasks.index', compact('tasks', 'route'));
    }
}
