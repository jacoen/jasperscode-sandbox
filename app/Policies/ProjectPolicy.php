<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('read project');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->can('read project');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create project');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->can('update project');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->can('delete project');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project)
    {
        return $user->can('restore project');
    }

    public function forceDelete(User $user, Project $project)
    {
        return $user->can('delete project') && $user->hasRole(['Admin', 'Super Admin']);
    }
}
