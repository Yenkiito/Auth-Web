<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\License;
use App\Models\User;
use App\Services\ProjectAccessService;

class LicensePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, License $license): bool
    {
        return app(ProjectAccessService::class)->license($user, $license);
    }

    public function create(User $user): bool
    {
        return $user->role !== Role::CLIENT;
    }

    public function update(User $user, License $license): bool
    {
        return $user->role !== Role::CLIENT && $this->view($user, $license);
    }

    public function delete(User $user, License $license): bool
    {
        return $user->role !== Role::CLIENT && $this->view($user, $license);
    }
}
