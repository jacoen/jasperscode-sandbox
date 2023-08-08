<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tasks = Task::with('project', 'project.manager', 'author', 'user')
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->when(! auth()->user()->hasRole(['Super Admin', 'Admin']), function ($query) {
                return $query->where('user_id', auth()->id());
            })
            ->latest('updated_at')
            ->orderbyDesc('id')
            ->paginate();

        if (! $tasks->count()) {
            return 'No tasks yet.';
        }

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request, Project $project)
    {
        if ($project->trashed()) {
            return response()
                ->json(['message' => 'Could not create the task because the related project has been trashed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = Arr::add($request->validated(), 'author_id', auth()->id());

        $task = $project->tasks()->create($data);

        return new TaskResource($task);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $task->load('project', 'author', 'user');

        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        if ($task->status == 'restored') {
            return redirect()->route('projects.show', $task->project)
                ->withErrors(['error' => 'You cannot update this task while the status is \'restored\'.']);
        }

        $task->update($request->validated());

        return new TaskResource($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return response('', Response::HTTP_NO_CONTENT);
    }

    public function trashed()
    {
        $tasks = Task::onlyTrashed()
            ->with('author', 'user')
            ->latest('deleted_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return TaskResource::collection($tasks);
    }

    public function restore(Task $task)
    {
        if ($task->project->trashed()) {
            return response()
                ->json(['message' => 'Could not restore this task because the project has been trashed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
