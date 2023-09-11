<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectTaskController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Project $project): AnonymousResourceCollection
    {
        $this->authorize('read task', Task::class);

        $data = $project->tasks()
            ->when(request()->search, function ($query) {
                $query->where('title', 'LIKE', '%'.request()->search.'%');
            })
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->with('author', 'user')
            ->latest('updated_at')
            ->orderByDesc('id')
            ->paginate();

        return TaskResource::collection($data);
    }
}
