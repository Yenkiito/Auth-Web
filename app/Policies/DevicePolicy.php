<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;
use App\Services\ProjectAccessService;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Device $device): bool
    {
        return app(ProjectAccessService::class)->device($user, $device);
    }

    public function update(User $user, Device $device): bool
    {
        return $this->view($user, $device);
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->isAdmin() || $this->view($user, $device);
    }
}
