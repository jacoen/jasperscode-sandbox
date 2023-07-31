<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Project $project)
    {
        $data = $project->tasks()
            ->with('author', 'user')
            ->latest('updated_at')
            ->orderByDesc('id')
            ->paginate();

        return TaskResource::collection($data);
    }
}
