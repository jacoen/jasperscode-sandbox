<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Post::class, 'post');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with('manager')
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $managers = User::role(['admin', 'manager'])->pluck('name', 'id');

        return view('projects.create', compact('managers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $project = Project::create($request->validated());

        if (auth()->id() != $project->manager_id) {
            User::find($project->manager_id)->notify(new ProjectAssignedNotification($project));
        }

        return redirect()->route('projects.index')
            ->with('success', 'A new project has been created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $managers = User::role(['admin', 'manager'])->pluck('name', 'id');

        return view('projects.edit', compact(['project', 'managers']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update($request->validated());

        if ($project->wasChanged('manager_id') && auth()->id() != $project->manager_id) {
            User::find($project->manager_id)->notify(new ProjectAssignedNotification($project));
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'The project has been updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'A project has been deleted');
    }
}
