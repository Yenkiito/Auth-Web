<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Partner;
use App\Models\User;
use App\Services\ProjectAccessService;

class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== Role::CLIENT;
    }

    public function view(User $user, Partner $partner): bool
    {
        return app(ProjectAccessService::class)->partner($user, $partner);
    }

    public function create(User $user): bool
    {
        return $user->role !== Role::CLIENT;
    }

    public function update(User $user, Partner $partner): bool
    {
        return app(ProjectAccessService::class)->partner($user, $partner);
    }

    public function delete(User $user, Partner $partner): bool
    {
        return $this->update($user, $partner);
    }
}
