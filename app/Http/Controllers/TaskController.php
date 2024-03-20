<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidProjectStatusException;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
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

    public function create(Project $project): View|RedirectResponse
    {
        if (! $project->is_open_or_pending) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['error' => 'Cannot create a task when the project is inactive.']);
        }

        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');

        return view('tasks.create', compact(['employees', 'project']));
    }

    public function store(Project $project, StoreTaskRequest $request): RedirectResponse
    {
        try {
            $this->taskService->storeTask($project, $request->validated(), $request->file('attachments'));

            return redirect()->route('projects.show', $project)
                ->with('success', 'A new task has been created.');
        } catch (InvalidProjectStatusException $e) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['errors' => $e->getMessage()]);
        }
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
                    'errors' => 'Could not update the task because the project is inactive.',
                ]);
        }

        $task->load('project');

        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');
        $statuses = array_merge(config('definitions.statuses'), ['Restored' => 'restored']);

        return view('tasks.edit', compact(['task', 'employees', 'statuses']));
    }

    public function update(Task $task, UpdateTaskRequest $request): RedirectResponse
    {
        try {
            $task = $this->taskService->updateTask($task, $request->validated(),  $request->file('attachments'));
    
            return redirect()->route('tasks.show', $task)
                ->with('success', 'The task '.$task->title.' has been updated.');
        } catch (InvalidProjectStatusException $e) {
            return redirect()->route('projects.show', $task->project)
                ->withErrors(['errors' => $e->getMessage()]);
        }
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

        try {
            $this->taskService->restoreTask($task);

            return redirect()->route('projects.show', $task->project)
                ->with('success', 'The task '.$task->title.'has been restored.');
        } catch (\Exception $e) {
            return redirect()->route('tasks.trashed')
                ->withErrors(['error' => $e->getMessage()]);
        }
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
