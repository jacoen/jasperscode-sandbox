<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    /**
     * @see app\Observers\ProjectObserver for the model events
     */
    public function __construct(private ProjectService $projectService)
    {
        $this->authorizeResource(Project::class, 'project');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $projects = $this->projectService->listProjects(
            request()->input('search'),
            request()->input('status'),
        );

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): ProjectResource
    {
        $project = $this->projectService->storeProject($request->validated());

        return new ProjectResource($project);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): ProjectResource
    {
        return new ProjectResource($project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->projectService->updateProject($project, $request->validated());

        return new ProjectResource($project);
    }

    public function destroy(Project $project): HttpResponse|JsonResponse
    {
        try {
            $this->projectService->destroy($project);

            return response()->json('', Response::HTTP_NO_CONTENT);
        } catch (RequestException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function trashed(): AnonymousResourceCollection
    {
        $this->authorize('restore project', Project::class);

        $projects = $this->projectService->listTrashedProjects();

        return ProjectResource::collection($projects);
    }

    public function restore(Project $project): ProjectResource|JsonResponse
    {
        $this->authorize('restore project', $project);

        if (! $project->trashed()) {
            return response()->json([
                'message' => 'This project cannot be restored, because it has not been deleted.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $project->restore();

        return new ProjectResource($project);
    }
}
