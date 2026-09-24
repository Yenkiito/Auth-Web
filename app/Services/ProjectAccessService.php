<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Device;
use App\Models\License;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;

class ProjectAccessService
{
    public function __construct(private PartnerHierarchyService $hierarchy) {}

    public function project(User $actor, Project $project): bool
    {
        return $actor->isAdmin() || $actor->project_id === $project->id;
    }

    public function partner(User $actor, Partner $partner): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return $actor->role === Role::PARTNER && $actor->partner && $this->hierarchy->contains($actor->partner, $partner);
    }

    public function user(User $actor, User $subject): bool
    {
        if ($actor->isAdmin() || $actor->id === $subject->id) {
            return true;
        }

        return $actor->role === Role::PARTNER && $subject->project_id === $actor->project_id && $subject->partner_id && in_array($subject->partner_id, $this->hierarchy->descendantIds($actor->partner), true);
    }

    public function license(User $actor, License $license): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }
        if ($actor->role === Role::CLIENT) {
            return $license->user_id === $actor->id;
        }

        return $actor->role === Role::PARTNER && $license->project_id === $actor->project_id && $license->partner_id && in_array($license->partner_id, $this->hierarchy->descendantIds($actor->partner), true);
    }

    public function device(User $actor, Device $device): bool
    {
        return $device->project_id === ($actor->project_id ?? $device->project_id) && ($actor->isAdmin() || $device->user_id === $actor->id || $this->license($actor, $device->license));
    }
}
