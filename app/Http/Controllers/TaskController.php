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
        $url = Route::currentRouteName();
        $tasks = Task::with('project', 'author', 'user')
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('tasks.index', compact('tasks', 'url'));
    }

    public function create(Project $project): View|RedirectResponse
    {
        if(! $project->is_open_or_pending) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['error' => 'Cannot create a task when the project is not open or pending.']);
        }

        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');

        return view('tasks.create', compact(['employees', 'project']));
    }

    public function store(Project $project, StoreTaskRequest $request): RedirectResponse
    {
        if(! $project->is_open_or_pending) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['error' => 'Cannot create a task when the project is not open or pending.']);
        }

        $data = Arr::add($request->validated(), 'author_id', auth()->id());

        $task = $project->tasks()->create($data);

        if (isset($request->user_id) && auth()->id() != $request->user_id) {
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
        $task->load('project');

        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');
        $statuses = Arr::add(config('definitions.statuses'), 'Restored', 'restored');

        return view('tasks.edit', compact(['task','employees', 'statuses']));
    }

    public function update(Task $task, UpdateTaskRequest $request): RedirectResponse
    {
        if ($task->status == 'restored') {
            return redirect()->route('projects.show', $task->project)
                ->withErrors(['error' => 'You cannot update this task while the status is \'restored\'.']);
        }

        $task->update($request->validated());

        if ($task->wasChanged('title')) {
            $task = $task->fresh();
        }

        if ($task->wasChanged('user_id') && isset($request->user_id) && auth()->id() != $request->user_id) {
            User::find($request->user_id)->notify(new TaskAssignedNotification($task));
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'The task '.$task->title  .' has been updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $project = Project::findOrFail($task->project_id);
        
        $taskTitle = $task->title;

        $task->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'The task '.$taskTitle.' has been deleted.');
    }

    public function restore(Task $task) 
    {
        $this->authorize('restore', $task);

        if ($task->project->trashed()) {
            return redirect()->route('tasks.trashed')
                ->withErrors(['error' => 'Could not restore task because the project has been deleted.']);
        }

        if ($task->project->status == 'closed' || $task->project->status == 'completed') {
            return redirect()
                ->route('tasks.trashed')->withErrors(['error' => 'Could not restore task becaues the project is either closed or completed.']);
        }

        $task->restore();

        return redirect()->route('tasks.trashed')
            ->with('success', 'The task '.$task->title. 'has been restored.');
    }

    public function userTasks()
    {
        $this->authorize('read task', Task::class);

        $url = Route::currentRouteName();
        $tasks = Task::with('project', 'author', 'user')
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->where('user_id', auth()->id())
            ->latest('updated_at')
            ->orderByDesc('id')
            ->paginate();

        return view('tasks.index', compact('tasks', 'url'));
    }
}
