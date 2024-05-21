<?php

namespace App\Services;

use App\Exceptions\InvalidPinnedProjectException;
use App\Exceptions\PinnedProjectDestructionException;
use App\Exceptions\UnauthorizedPinException;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use Carbon\Carbon;
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
            ->whereIn('status', ['open', 'pending', 'closed', 'restored'])
            ->where('due_date', '>=', now()->startOfDay())
            ->orWhere('status', 'completed')
            ->whereNot('status', 'expired')
            ->latest('updated_at')
            ->orderBy('id', 'desc')
            ->paginate(15);
    }

    public function storeProject(array $projectData): Project
    {
        $project = Project::create($projectData);

        if (isset($projectData['manager_id'])) {
            User::find($projectData['manager_id'])->notify(new ProjectAssignedNotification($project));
        }

        return $project;
    }

    public function updateProject(Project $project, array $validatedData): Project
    {
        $pinnedProject = Project::where('is_pinned', true)->first();

        if (! auth()->user()->can('pin project') && $validatedData['is_pinned']) {
            throw new UnauthorizedPinException('You are not authorized to pin a project.', $project);
        }

        if ($validatedData['is_pinned'] && $pinnedProject && $pinnedProject->id != $project->id) {
            throw new InvalidPinnedProjectException('There is a pinned project already. If you want to pin this project you will have to unpin the other project.', $project);
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
            throw new PinnedProjectDestructionException('Cannot delete a project that is pinned.');
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
    
    public function listExpiredProjects(string $yearWeek = null): LengthAwarePaginator
    {   
        $dates = '';

        if ($yearWeek) {
            $dates = $this->spliceYearWeek($yearWeek);
        }

        return Project::with('manager')
            ->when($yearWeek, function($query) use ($dates) {
                $query->whereBetween('due_date', [$dates['startDate'], $dates['endDate']]);
            })
            ->where('due_date', '<', now()->startOfDay())
            ->whereNot('status', 'completed')
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    private function spliceYearWeek($yearWeek): array
    {
        $parts = explode('-', $yearWeek);
        $year =  (int)$parts[0];
        $week = (int)$parts[1];

        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endOfWeek = Carbon::now()->setISODate($year, $week)->endOfWeek();

        return [
            'startDate' => $startOfWeek, 
            'endDate' => $endOfWeek
        ];
    }
}