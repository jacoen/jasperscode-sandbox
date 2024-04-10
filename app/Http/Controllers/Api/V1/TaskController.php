<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InvalidProjectStatusException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService)
    {
        $this->authorizeResource(Task::class, 'task');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $tasks = $this->taskService->listTasks(
            request()->input('search'),
            request()->input('status'),
        );

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request, Project $project): TaskResource|JsonResponse
    {
        try {
            $task = $this->taskService->storeTask($project, $request->validated(), $request->file('attachments'));

            return new TaskResource($task);
        } catch(InvalidProjectStatusException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
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
        try {
            $data = $this->taskService->updateTask($task, $request->validated(), $request->file('attachments'));

            return new TaskResource($data);
        } catch(InvalidProjectStatusException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
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

        $tasks = $this->taskService->trashedTasks();

        return TaskResource::collection($tasks);
    }

    public function restore(Task $task): TaskResource|JsonResponse
    {
        $this->authorize('restore task', $task);

        try {
            $this->taskService->restoreTask($task);

            return new TaskResource($task);
        } catch (InvalidProjectStatusException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function userTasks(): AnonymousResourceCollection
    {
        $this->authorize('read task', Task::class);

        $tasks = $this->taskService->listTasks(
            request()->input('search'),
            request()->input('status'),
            auth()->id()
        );

        return TaskResource::collection($tasks);

        return TaskResource::collection($tasks);
    }
}
