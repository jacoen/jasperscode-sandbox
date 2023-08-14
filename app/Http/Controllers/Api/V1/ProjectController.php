<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with('manager')
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->when(auth()->user()->hasRole(['Admin', 'Super Admin']), function ($query) {
                $query->orderBy('is_pinned', 'desc');
            })
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $project = Project::create($request->validated());

        return new ProjectResource($project);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        return new ProjectResource($project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update($request->validated());

        return new ProjectResource($project);
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return response('', Response::HTTP_NO_CONTENT);
    }

    public function trashed()
    {
        $this->authorize('restore project', Project::class);

        $projects = Project::onlyTrashed()
            ->with('manager')
            ->latest('deleted_at')
            ->orderByDesc('id')
            ->paginate();

        return ProjectResource::collection($projects);
    }

    public function restore(Project $project)
    {
        $this->authorize('restore project', $project);

        $project->restore();

        return new ProjectResource($project);
    }
}
