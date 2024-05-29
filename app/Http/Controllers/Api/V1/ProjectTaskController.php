<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectTaskController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Project $project, TaskService $taskService): AnonymousResourceCollection
    {
        $this->authorize('read task', Task::class);

        $data = $taskService->findTasksByProject($project, request()->input('search'), request()->input('status'));

        return TaskResource::collection($data);
    }
}
