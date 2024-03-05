<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function listProjects($search = null, $status = null): LengthAwarePaginator
    {
        return Project::with('manager')
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'LIKE', '%'.$search.'%');
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(auth()->user()->hasRole(['Admin', 'Super Admin']), function ($query) {
                $query->orderBy('is_pinned', 'desc');
            })
            ->whereNot('status', 'expired')
            ->where('due_date', '>=', now()->startOfDay())
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate(15);
    }

    public function store(array $projectData): Project
    {
        $project = $project = Project::create($projectData);

        if (isset($projectData['manager_id'])) {
            User::find($projectData['manager_id'])->notify(new ProjectAssignedNotification($project));
        }

        return $project;
    }

    public function update(Project $project, array $validatedData): Project
    {
        if (! auth()->user()->can('pin project') && $validatedData['is_pinned']) {
            throw new \Exception('User is not authorized to pin a project');
        }

        if ($validatedData['is_pinned'] && $this->getPinnedProject() && $this->getPinnedProject()->id != $project->id) {
            throw new \Exception('There is a pinned project already. If you want to pin this project you will have to unpin the other project.');
        }

        $project->update($validatedData);

        if (isset($validatedData['manager_id']) && $project->wasChanged('manager_id')) {
            User::find($validatedData['manager_id'])->notify(new ProjectAssignedNotification($project));
        }

        return $project;
    }

    public function destroy(Project $project): void
    {
        if ($project->is_pinned) {
            throw new \Exception('Project could not be deleted because it was pinned.
            Remove the pin from the project before deleting it.');
        }

        $project->delete();
    }

    public function listTrashedProjects(): LengthAwarePaginator
    {
        return Project::onlyTrashed()
        ->with('manager')
        ->latest('deleted_at')
        ->orderBy('id', 'desc')
        ->paginate();
    }

    public function getManagers()
    {
        return User::role(['Admin', 'Manager'])->pluck('name', 'id');
    }

    public function getPinnedProject()
    {
        return Project::where('is_pinned', true)->first();
    }
}