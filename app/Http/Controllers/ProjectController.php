<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

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
    public function index(): View
    {
        $projects = Project::with('manager')
            ->when(request('search'), function ($query) {
                $query->where('title', 'LIKE', '%'.request('search').'%');
            })
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->when(auth()->user()->hasRole(['Admin', 'Super Admin']), function ($query) {
                $query->orderBy('is_pinned', 'desc');
            })
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('projects.index', compact(['projects']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $managers = User::role(['Admin', 'Manager'])->pluck('name', 'id');

        return view('projects.create', compact('managers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
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
    public function show(Project $project): View
    {
        $pending_or_open = $project->is_open_or_pending;

        $tasks = $project->tasks()
            ->when(request()->search, function ($query) {
                $query->where('title', 'LIKE', '%'.request()->search.'%');
            })
            ->when(request()->status, function ($query) {
                $query->where('status', request()->status);
            })
            ->with('author', 'user')
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('projects.show', compact(['project', 'tasks', 'pending_or_open']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): View
    {
        $managers = User::role(['Admin', 'Manager'])->pluck('name', 'id');
        $statuses = Arr::add(config('definitions.statuses'), 'Restored', 'restored');

        return view('projects.edit', compact(['project', 'managers', 'statuses']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        if (! auth()->user()->can('pin project') && $request->is_pinned) {
            return back()->withErrors(['error' => 'User is not authorized to pin a project']);
        }

        if (Project::where('is_pinned', true)->count() >= 1 && $request->is_pinned) {
            return back()
                ->withErrors(['error' => 'There is a pinned project already. If you want to pin this project you will have to unpin the other project.']);
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
    public function destroy(Project $project): RedirectResponse
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

    public function restore(Project $project): RedirectResponse
    {
        $this->authorize('restore', $project);

        $project->restore();

        return redirect()->route('projects.trashed')
            ->with('success', 'The project '.$project->title.' has been restored.');
    }
}
