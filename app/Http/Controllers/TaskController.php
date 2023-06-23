<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index()
    {
        $query = Task::query();
        
        if (! auth()->user()->hasRole('Admin')) {
            $query = $query->where('user_id', auth()->id());
        }
        
        $tasks = $query->with('project', 'author', 'user')
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('tasks.index', compact('tasks'));
    }

    public function create(Project $project): View
    {
        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');

        return view('tasks.create', compact(['employees', 'project']));
    }

    public function store(Project $project, TaskRequest $request): RedirectResponse
    {
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

    public function edit(Project $project, Task $task): View
    {
        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');

        return view('tasks.edit', compact(['task', 'project', 'employees']));
    }

    public function update(Task $task, TaskRequest $request): RedirectResponse
    {
        $task->update($request->validated());

        if ($task->wasChanged('title')) {
            $task = $task->fresh();
        }

        if ($task->wasChanged('user_id') && isset($request->user_id) && auth()->id() != $request->user_id) {
            User::find($request->user_id)->notify(new TaskAssignedNotification($task));
        }

        return redirect()->route('tasks.show', $task)
            ->with('success', 'The task has been updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $taskTitle = $task->title;

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'The task '.$taskTitle.' has been deleted');
    }
}
