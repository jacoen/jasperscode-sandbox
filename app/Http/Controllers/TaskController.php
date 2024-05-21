<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * @see app\Observers\TaskObserver for the model events
     */
    public function __construct(private TaskService $taskService)
    {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(): View
    {
        $route = Route::currentRouteName();
        $tasks = $this->taskService->listTasks(
            request()->search,
            request()->status,
            auth()->id(),
        );

        return view('tasks.index', compact('tasks', 'route'));
    }

    public function create(Project $project, UserService $userService): View|RedirectResponse
    {
        if (! $project->is_open_or_pending) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['error' => 'Cannot create a task when the project is inactive.']);
        }

        $employees = $userService->getUsersByRoles(['Admin', 'Manager', 'Employee']);

        return view('tasks.create', compact(['employees', 'project']));
    }

    public function store(Project $project, StoreTaskRequest $request): RedirectResponse
    {
        $this->taskService->storeTask($project, $request->validated(), $request->file('attachments'));

        return redirect()->route('projects.show', $project)
            ->with('success', 'A new task has been created.');
    }

    public function show(Task $task): View
    {
        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task, UserService $userService): View|RedirectResponse
    {
        if (! $task->project->is_open_or_pending) {
            return redirect()->route('projects.show', $task->project)
                ->withErrors([
                    'error' => 'Cannot edit the task because the related project is inactive.',
                ]);
        }

        $employees = $userService->getUsersByRoles(['Admin', 'Manager', 'Employee']);
        $statuses = array_merge(config('definitions.statuses'), [
            'Restored' => 'restored',
        ]);

        return view('tasks.edit', compact(['task', 'employees', 'statuses']));
    }

    public function update(Task $task, UpdateTaskRequest $request): RedirectResponse
    {
        $task = $this->taskService->updateTask(
            $task,
            $request->validated(),
            $request->file('attachments'),
        );

        return redirect()->route('tasks.show', $task)
            ->with('success', 'The task '.$task->title.' has been updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $project = $task->project_id;

        $task->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'The task has been deleted.');
    }

    public function trashed(): View
    {
        $this->authorize('trashed', Task::class);

        $tasks = $this->taskService->trashedTasks();

        return view('tasks.trashed', compact('tasks'));
    }

    public function restore(Task $task): RedirectResponse
    {
        $this->authorize('restore', $task);

        $this->taskService->restoreTask($task);

        return redirect()->route('tasks.trashed')
            ->with('success', 'The task has been restored.');
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
        $this->authorize('adminTasks', Task::class);

        $route = Route::currentRouteName();
        $tasks = $this->taskService->listTasks(
            request()->search,
            request()->status,
        );

        return view('tasks.index', compact('tasks', 'route'));
    }
}
