<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Partner;
use App\Models\User;
use App\Services\ActiveProjectService;
use App\Services\ActivityLogger;
use App\Services\PartnerHierarchyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request, PartnerHierarchyService $hierarchy, ActiveProjectService $activeProjects): Response
    {
        $this->authorize('viewAny', User::class);
        $actor = $request->user();
        $activeProject = $activeProjects->get($request, $actor);
        $query = User::clients()->with(['project:id,name', 'partner:id,name']);
        if ($actor->isAdmin()) {
            $query->where('project_id', $activeProject?->id);
        } else {
            $query->where('project_id', $actor->project_id)->whereIn('partner_id', $hierarchy->descendantIds($actor->partner));
        }

        return Inertia::render('Users/Index', ['items' => $query->latest()->paginate(25)]);
    }

    public function store(StoreUserRequest $request, PartnerHierarchyService $hierarchy, ActiveProjectService $activeProjects, ActivityLogger $log): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();
        $projectId = $actor->isAdmin() ? $activeProjects->get($request, $actor)?->id : $actor->project_id;
        $partnerId = $actor->role === Role::PARTNER ? $actor->partner_id : null;
        if ($partnerId) {
            $partner = Partner::findOrFail($partnerId);
            abort_unless($partner->project_id === $projectId && ($actor->isAdmin() || $hierarchy->contains($actor->partner, $partner)), 403);
        }
        abort_unless($projectId, 422, 'Selecciona una aplicación antes de crear usuarios.');
        $user = User::create([
            'name' => $data['username'], 'username' => $data['username'], 'email' => null,
            'password' => $data['password'], 'project_id' => $projectId, 'partner_id' => $partnerId,
            'role' => Role::CLIENT, 'status' => 'active', 'expires_at' => Carbon::parse($data['expiration'])->endOfDay(),
            'hwid_affected' => $request->boolean('hwid_affected'),
        ]);
        $log->log('user.created', ['subject_id' => $user->id], projectId: $projectId, partnerId: $partnerId);

        return back()->with('success', 'Usuario creado.');
    }

    public function update(UpdateUserRequest $request, User $user, PartnerHierarchyService $hierarchy, ActivityLogger $log): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $user->role === Role::CLIENT, 403);
        $data = $request->validated();
        $partnerId = $data['partner_id'] ?? null;
        if ($partnerId) {
            $partner = Partner::findOrFail($partnerId);
            abort_unless($partner->project_id === $user->project_id && ($request->user()->isAdmin() || $hierarchy->contains($request->user()->partner, $partner)), 403);
        }
        $user->update(['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'] ?? null, 'partner_id' => $partnerId, 'status' => $data['status'], ...(! empty($data['password']) ? ['password' => $data['password']] : [])]);
        $log->log('user.updated', ['subject_id' => $user->id], projectId: $user->project_id, partnerId: $user->partner_id);

        return back()->with('success', 'Usuario actualizado.');
    }
}
