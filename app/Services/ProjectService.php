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
    public function listProjects(User $user, string $search = null, string $status = null): LengthAwarePaginator
    {
        return Project::with('manager')
            ->search($search)
            ->filterStatus($status)
            ->when(! $search && ! $status, function ($query) {
                $query->defaultFilter();
            })
            ->when($user->hasRole(['Admin', 'Super Admin']), function ($query) {
                $query->orderBy('is_pinned', 'desc');
            })
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
            throw new PinnedProjectDestructionException('Cannot delete a pinned project.');
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
        $query = Project::with('manager')
        ->where('due_date', '<', now()->startOfDay())
        ->whereNot('status', 'completed')
        ->orderByDesc('due_date')
        ->orderByDesc('id');

        $query->when($yearWeek, function($query) use ($yearWeek) {
            $dates = $this->spliceYearWeek($yearWeek);
            return $query->whereBetween('due_date', [$dates['startDate'], $dates['endDate']]);
        });

        return $query->paginate(15);
    }

    private function spliceYearWeek(string $yearWeek): array
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
