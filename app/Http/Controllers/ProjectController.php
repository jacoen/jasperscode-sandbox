<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;

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
    public function index()
    {
        $pinned_project = Project::where('is_pinned', true)
            ->with('manager')
            ->first();

        $projects = Project::query()
            ->when(auth()->user()->hasRole(['Admin', 'Super Admin']), function ($query) {
                return $query->where('is_pinned', false);
            })->with('manager')
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('projects.index', compact(['pinned_project', 'projects']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $managers = User::role(['Admin', 'Manager'])->pluck('name', 'id');

        return view('projects.create', compact('managers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $project = Project::create($request->validated());

        if (isset($request->manager_id) && auth()->id() != $project->manager_id) {
            User::find($project->manager_id)->notify(new ProjectAssignedNotification($project));
        }

        return redirect()->route('projects.index')
            ->with('success', 'A new project has been created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $tasks = $project->tasks()
            ->with('author', 'user')
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('projects.show', compact('project', 'tasks'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $managers = User::role(['Admin', 'Manager'])->pluck('name', 'id');

        return view('projects.edit', compact(['project', 'managers']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        if (! auth()->user()->can('pin project') && $request->is_pinned) {
            return back()->withErrors(['error' => 'User is not authorized to pin a project']);
        }

        if (Project::where('is_pinned', true)->count() > 1) {
            return back()
                ->withErrors(['error' => 'There is a pinned project already.
             If you want to pin this project you will have to unpin the other project.']);
        }

        $project->update($request->validated());

        if (isset($request->manager_id) && $project->wasChanged('manager_id') && auth()->id() != $project->manager_id) {
            User::find($project->manager_id)->notify(new ProjectAssignedNotification($project));
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'The project has been updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        if ($project->is_pinned) {
            return back()
                ->withErrors(['error' => 'Project could not be deleted because it was pinned.
                Remove the pin from the project before deleting it.']);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'The project has been deleted.');
    }
}
