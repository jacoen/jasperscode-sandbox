<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $tasks = Task::with('project', 'project.manager', 'author', 'user')
            ->when(request()->search, function ($query) {
                $query->where('title', 'LIKE', '%'.request()->search.'%');
            })
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->latest('updated_at')
            ->latest('id')
            ->paginate();

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request, Project $project): TaskResource|JsonResponse
    {
        if (! $project->is_open_or_pending) {
            return response()->json([
                'message' => 'Cannot create a task when the project is not open or pending.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
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

        if (isset($request->user_id) && auth()->id() != $request->user_id) {
            User::find($request->user_id)->notify(new TaskAssignedNotification($task));
        }

        return new TaskResource($task);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): TaskResource
    {
        $task->load('project', 'author', 'user');

        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource|JsonResponse
    {
        if (! $task->project->is_open_or_pending) {
            return response()->json([
                'message' => 'Cannot update the task when the project is not open or pending.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $task->update($request->validated());

        if ($attachments = $request->file('attachments')) {
            $task->clearMediaCollection();
            foreach ($attachments as $attachment)
            {
                $task->addMedia($attachment)
                ->usingName($task->title)
                ->toMediaCollection('attachments');
            }
        }

        if ($task->wasChanged('user_id') && isset($request->user_id) && auth()->id() != $request->user_id) {
            User::find($request->user_id)->notify(new TaskAssignedNotification($task));
        }

        return new TaskResource($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json('', Response::HTTP_NO_CONTENT);
    }

    public function trashed(): AnonymousResourceCollection
    {
        $this->authorize('restore task', Task::class);

        $tasks = Task::onlyTrashed()
            ->with('author', 'user')
            ->latest('deleted_at')
            ->latest('id')
            ->paginate();

        return TaskResource::collection($tasks);
    }

    public function restore(Task $task): TaskResource|JsonResponse
    {
        $this->authorize('restore task', $task);

        if (! $task->trashed()) {
            return response()->json([
                'message' => 'This task cannot be restored, because it has not been deleted.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($task->project->trashed()) {
            return response()->json([
                'message' => 'Could not restore this task because the project has been trashed.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $task->restore();

        return new TaskResource($task);
    }

    public function userTasks(): AnonymousResourceCollection
    {
        $this->authorize('read task', Task::class);

        $tasks = Task::with('project', 'project.manager', 'author', 'user')
            ->when(request()->search, function ($query) {
                $query->where('title', 'LIKE', '%'.request()->search.'%');
            })
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->where('user_id', auth()->id())
            ->latest('updated_at')
            ->latest('id')
            ->paginate();

        return TaskResource::collection($tasks);
    }
}
