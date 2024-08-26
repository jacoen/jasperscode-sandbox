<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use App\Services\TaskService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    

    /**
     * @see app\Observers\ProjectObserver for the model events
     */
    public function __construct(private ProjectService $projectService)
    {
        $this->authorizeResource(Project::class, 'project');
        $this->projectService = $projectService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $projects = $this->projectService->listProjects(
            auth()->user(),
            request()->input('search'),
            request()->input('status'),
        );

        return view('projects.index', compact(['projects']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(UserService $userService): View
    {
        $managers = $userService->getUsersByRoles(['Admin', 'Manager']);

        return view('projects.create', compact('managers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->projectService->storeProject($request->validated());

        return redirect()->route('projects.index')
            ->with('success', 'A new project has been created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project, TaskService $taskService): View
    {
        $pending_or_open = $project->is_open_or_pending;

        $tasks = $taskService->findTasksByProject($project, request()->search, request()->status);

        return view('projects.show', compact(['project', 'tasks', 'pending_or_open']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project, UserService $userService): View
    {
        $managers = $userService->getUsersByRoles(['Admin', 'Manager']);
        $statuses = array_merge(config('definitions.statuses'), [
            'Restored' => 'restored',
            'Expired' => 'expired',
        ]);

        return view('projects.edit', compact(['project', 'managers', 'statuses']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $updatedProject = $this->projectService->updateProject($project, $request->validated());

        return redirect()->route('projects.show', $updatedProject)
            ->with('success', 'The project has been updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->projectService->destroy($project);

        return redirect()->route('projects.index')
            ->with('success', 'The project has been deleted.');
    }

    public function trashed(): View
    {
        $this->authorize('restore project', Project::class);

        $projects = $this->projectService->listTrashedProjects();

        return view('projects.trashed', compact('projects'));
    }

    public function restore(Project $project): RedirectResponse
    {
        $this->authorize('restore project', $project);

        $project->restore();

        return redirect()->route('projects.trashed')
            ->with('success', 'The project '.$project->title.' has been restored.');
    }

    public function forceDelete(Project $project): RedirectResponse
    {
        $this->authorize('forceDelete', $project);

        $project->forceDelete();

        return redirect()->route('projects.trashed')
            ->with('success', 'The project has been permanently deleted.');
    }
}
