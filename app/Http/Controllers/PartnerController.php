<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Models\Partner;
use App\Models\User;
use App\Services\ActiveProjectService;
use App\Services\ActivityLogger;
use App\Services\PartnerHierarchyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function index(Request $request, PartnerHierarchyService $hierarchy, ActiveProjectService $activeProjects): Response
    {
        $this->authorize('viewAny', Partner::class);
        $actor = $request->user();
        $query = Partner::with(['account:id,username,email,last_login_at', 'project:id,name'])->withCount(['children', 'clients', 'licenses']);
        if ($actor->isAdmin()) {
            $query->where('project_id', $activeProjects->get($request, $actor)?->id);
        } else {
            $query->whereIn('id', $hierarchy->descendantIds($actor->partner));
        }

        $items = $query->orderBy('parent_id')->paginate(50);
        $items->through(function (Partner $partner) use ($request) {
            $canDelete = $request->user()->can('delete', $partner);
            $hasDependencies = $partner->children_count > 0 || $partner->clients_count > 0 || $partner->licenses_count > 0;
            $partner->setAttribute('can_delete', $canDelete && ! $hasDependencies);
            $partner->setAttribute('delete_block_reason', $canDelete && $hasDependencies
                ? 'Elimina o reasigna primero sus subsocios, clientes y licencias.'
                : null);

            return $partner;
        });

        return Inertia::render('Partners/Index', ['items' => $items, 'projects' => []]);
    }

    public function store(StorePartnerRequest $request, ActiveProjectService $activeProjects, ActivityLogger $log): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();
        $projectId = $actor->isAdmin() ? $activeProjects->get($request, $actor)?->id : $actor->project_id;
        abort_unless($projectId, 422, 'Selecciona una aplicación.');
        $parentId = $actor->role === Role::PARTNER ? $actor->partner_id : ($data['parent_id'] ?? null);
        if ($parentId) {
            abort_unless(Partner::whereKey($parentId)->where('project_id', $projectId)->exists(), 422);
        }
        $partner = DB::transaction(function () use ($data, $projectId, $parentId) {
            $account = User::create(['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'] ?? null, 'password' => $data['password'], 'role' => Role::PARTNER, 'status' => $data['status'], 'project_id' => $projectId]);
            $partner = Partner::create(['project_id' => $projectId, 'parent_id' => $parentId, 'user_id' => $account->id, 'name' => $data['name'], 'status' => $data['status']]);
            $account->update(['partner_id' => $partner->id]);

            return $partner;
        });
        $log->log($parentId ? 'partner.child_created' : 'partner.created', ['partner_id' => $partner->id], projectId: $projectId, partnerId: $partner->id);

        return back()->with('success', 'Socio creado.');
    }

    public function update(UpdatePartnerRequest $request, Partner $partner, PartnerHierarchyService $hierarchy, ActivityLogger $log): RedirectResponse
    {
        $data = $request->validated();
        $parentId = $request->user()->isAdmin() ? ($data['parent_id'] ?? null) : $partner->parent_id;
        $parent = $parentId ? Partner::find($parentId) : null;
        $hierarchy->assertValidParent($partner, $parent);
        DB::transaction(function () use ($partner, $data, $parentId) {
            $partner->update(['name' => $data['name'], 'parent_id' => $parentId, 'status' => $data['status']]);
            $partner->account->update(['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'] ?? null, 'status' => $data['status'], ...(! empty($data['password']) ? ['password' => $data['password']] : [])]);
        });
        $log->log('partner.updated', ['partner_id' => $partner->id], projectId: $partner->project_id, partnerId: $partner->id);

        return back()->with('success', 'Socio actualizado.');
    }

    public function destroy(Request $request, Partner $partner, ActiveProjectService $activeProjects, ActivityLogger $log): RedirectResponse
    {
        $this->authorize('delete', $partner);

        if ($request->user()->isAdmin()) {
            abort_unless($activeProjects->get($request, $request->user())?->id === $partner->project_id, 403);
        }

        $partner->loadCount(['children', 'clients', 'licenses']);
        if ($partner->children_count > 0 || $partner->clients_count > 0 || $partner->licenses_count > 0) {
            return back()->with('error', 'No se puede eliminar: reasigna o elimina primero sus subsocios, clientes y licencias.');
        }

        $account = $partner->account;
        $partnerId = $partner->id;
        $partnerName = $partner->name;
        $projectId = $partner->project_id;

        DB::transaction(function () use ($partner, $account) {
            DB::table('sessions')->where('user_id', $account->id)->delete();
            $account->forceFill(['status' => 'blocked', 'remember_token' => null])->save();
            $partner->delete();
        });

        $log->log('partner.deleted', ['partner_id' => $partnerId, 'partner_name' => $partnerName], projectId: $projectId);

        return back()->with('success', 'Socio eliminado y cuenta desactivada.');
    }
}
