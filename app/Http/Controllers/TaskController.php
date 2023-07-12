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

    public function index()
    {
        $query = Task::query();

        if (! auth()->user()->hasRole(['Admin', 'Super Admin'])) {
            $query = $query->where('user_id', auth()->id());
        }

        $tasks = $query->with('project', 'author', 'user')
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('tasks.index', compact('tasks'));
    }

    public function create(Project $project): View|RedirectResponse
    {
        if(! $project->is_open_or_pending) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['error' => 'Cannot create a task when the project is not open or pending']);
        }

        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');

        return view('tasks.create', compact(['employees', 'project']));
    }

    public function store(Project $project, StoreTaskRequest $request): RedirectResponse
    {
        if(! $project->is_open_or_pending) {
            return redirect()->route('projects.show', $project)
                ->withErrors(['error' => 'Cannot create a task when the project is not open or pending']);
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

    public function edit(Project $project, Task $task): View|RedirectResponse
    {
        if(! $task->project->is_open_or_pending) {
            return redirect()->route('projects.show', $task->project)
                ->withErrors(['error' => 'Cannot edit a task when the project is not open or pending']);
        }

        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');

        return view('tasks.edit', compact(['task', 'project', 'employees']));
    }

    public function update(Task $task, UpdateTaskRequest $request): RedirectResponse
    {
        if(! $task->project->is_open_or_pending) {
            return redirect()->route('projects.show', $task->project)
                ->withErrors(['error' => 'Cannot edit a task when the project is not open or pending']);
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
            ->with('success', 'The task '.$taskTitle.' has been deleted');
    }
}
