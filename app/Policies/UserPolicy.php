<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Services\ProjectAccessService;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== Role::CLIENT;
    }

    public function view(User $user, User $subject): bool
    {
        return app(ProjectAccessService::class)->user($user, $subject);
    }

    public function create(User $user): bool
    {
        return $user->role !== Role::CLIENT;
    }

    public function update(User $user, User $subject): bool
    {
        return app(ProjectAccessService::class)->user($user, $subject);
    }

    public function delete(User $user, User $subject): bool
    {
        return $user->isAdmin() && $user->id !== $subject->id;
    }
}
