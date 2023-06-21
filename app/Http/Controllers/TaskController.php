<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(): View
    { 
        $tasks = Task::with(['project', 'author', 'user'])
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('tasks.index', compact('tasks'));
    }

    public function create(): View
    {
        $projects = Project::all()->pluck('title', 'id');
        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');

        return view('tasks.create', compact(['projects', 'employees']));
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $data = Arr::add($request->validated(), 'author_id', $request->user()->id);

        Task::create($data);

        return redirect()->route('tasks.index')
            ->with('success', 'A new task has been created.');
    }

    public function show(Task $task): View
    {
        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task): View
    {
        $projects = Project::all()->pluck('title', 'id');
        $employees = User::role(['Admin', 'Manager', 'Employee'])->pluck('name', 'id');

        return view('tasks.edit', compact(['task', 'projects', 'employees']));
    }

    public function update(Task $task, TaskRequest $request): RedirectResponse
    {
        $task->update($request->validated());

        if ($task->wasChanged('title')) {
            $task = $task->fresh();
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
