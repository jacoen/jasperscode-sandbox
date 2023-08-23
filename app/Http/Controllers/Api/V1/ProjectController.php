<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    /**
     * @see app\Observers\ProjectObserver for the model events
     */
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
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
    public function store(StoreProjectRequest $request): ProjectResource
    {
        $project = Project::create($request->validated());

        if (isset($request->manager_id) && auth()->id() != $project->manager_id) {
            User::find($project->manager_id)->notify(new ProjectAssignedNotification($project));
        }

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
        $project->update($request->validated());

        if (isset($request->manager_id) && $project->wasChanged('manager_id') && auth()->id() != $project->manager_id) {
            User::find($project->manager_id)->notify(new ProjectAssignedNotification($project));
        }

        return new ProjectResource($project);
    }

    public function destroy(Project $project): HttpResponse
    {
        $project->delete();

        return response('', Response::HTTP_NO_CONTENT);
    }

    public function trashed(): AnonymousResourceCollection
    {
        $this->authorize('restore project', Project::class);

        $projects = Project::onlyTrashed()
            ->with('manager')
            ->latest('deleted_at')
            ->orderByDesc('id')
            ->paginate();

        return ProjectResource::collection($projects);
    }

    public function restore(Project $project): ProjectResource | JsonResponse
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
