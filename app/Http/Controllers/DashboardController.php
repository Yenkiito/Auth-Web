<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\License;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use App\Services\ActiveProjectService;
use App\Services\PartnerHierarchyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PartnerHierarchyService $hierarchy, ActiveProjectService $activeProjects): Response
    {
        $user = $request->user();
        $projectId = $activeProjects->get($request, $user)?->id;
        $partnerIds = $user->role === Role::PARTNER ? $hierarchy->descendantIds($user->partner) : [];
        $licenses = License::query()->when($user->isAdmin(), fn ($q) => $q->where('project_id', $projectId))->when(! $user->isAdmin(), fn ($q) => $user->role === Role::CLIENT ? $q->where('user_id', $user->id) : $q->whereIn('partner_id', $partnerIds));
        $clients = User::clients()->when($user->isAdmin(), fn ($q) => $q->where('project_id', $projectId))->when(! $user->isAdmin(), fn ($q) => $user->role === Role::CLIENT ? $q->whereKey($user->id) : $q->whereIn('partner_id', $partnerIds));
        $partners = Partner::query()->when($user->isAdmin(), fn ($q) => $q->where('project_id', $projectId))->when(! $user->isAdmin(), fn ($q) => $q->whereIn('id', $partnerIds));
        $devices = Device::query()->when($user->isAdmin(), fn ($q) => $q->where('project_id', $projectId))->when(! $user->isAdmin(), fn ($q) => $user->role === Role::CLIENT ? $q->where('user_id', $user->id) : $q->whereHas('license', fn ($l) => $l->whereIn('partner_id', $partnerIds)));
        $logs = ActivityLog::query()->when($user->isAdmin(), fn ($q) => $q->where('project_id', $projectId))->when(! $user->isAdmin(), fn ($q) => $user->role === Role::CLIENT ? $q->where('user_id', $user->id) : $q->whereIn('partner_id', $partnerIds));

        return Inertia::render('Dashboard', ['stats' => [
            'projects' => $user->isAdmin() ? Project::count() : ($projectId ? 1 : 0),
            'partners' => $partners->count(), 'clients' => $clients->count(), 'licenses' => $licenses->count(),
            'active' => (clone $licenses)->where('status', 'active')->count(), 'expired' => (clone $licenses)->expired()->count(),
            'devices' => $devices->count(),
        ], 'activity' => $logs->latest('created_at')->limit(8)->get()]);
    }
}
