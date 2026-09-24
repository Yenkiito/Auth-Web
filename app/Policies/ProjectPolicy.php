<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccessService;

class ProjectPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === Role::OWNER ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Project $project): bool
    {
        return app(ProjectAccessService::class)->project($user, $project);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Project $project): bool
    {
        return app(ProjectAccessService::class)->project($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return app(ProjectAccessService::class)->project($user, $project);
    }
}
