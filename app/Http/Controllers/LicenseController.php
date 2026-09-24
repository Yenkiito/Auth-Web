<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\BulkGenerateLicenseRequest;
use App\Http\Requests\GenerateLicenseRequest;
use App\Http\Requests\UpdateLicenseRequest;
use App\Jobs\GenerateLicenseBatch;
use App\Models\License;
use App\Models\Partner;
use App\Models\User;
use App\Services\ActiveProjectService;
use App\Services\ActivityLogger;
use App\Services\LicenseGeneratorService;
use App\Services\PartnerHierarchyService;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LicenseController extends Controller
{
    public function index(Request $request, PartnerHierarchyService $hierarchy, ActiveProjectService $activeProjects): Response
    {
        $this->authorize('viewAny', License::class);
        $actor = $request->user();
        $activeProject = $activeProjects->get($request, $actor);
        $q = License::with(['project:id,name', 'partner:id,name', 'user:id,username']);
        if ($actor->role === Role::CLIENT) {
            $q->where('user_id', $actor->id);
        } elseif ($actor->isAdmin()) {
            $q->where('project_id', $activeProject?->id);
        } else {
            $q->where('project_id', $actor->project_id)->whereIn('partner_id', $hierarchy->descendantIds($actor->partner));
        }
        foreach (['project_id', 'partner_id', 'user_id', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $q->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('key')) {
            $q->where('key', 'like', '%'.$request->string('key').'%');
        }
        $partnerIds = $actor->isAdmin() ? null : ($actor->role === Role::PARTNER ? $hierarchy->descendantIds($actor->partner) : []);

        $projectId = $activeProject?->id;

        return Inertia::render('Licenses/Index', ['items' => $q->latest()->paginate(25)->withQueryString(), 'partners' => Partner::select('id', 'name', 'project_id')->when($actor->isAdmin(), fn ($p) => $p->where('project_id', $projectId))->when($partnerIds !== null, fn ($p) => $p->whereIn('id', $partnerIds))->get(), 'users' => User::clients()->select('id', 'username', 'project_id', 'partner_id')->when($actor->isAdmin(), fn ($u) => $u->where('project_id', $projectId))->when($partnerIds !== null, fn ($u) => $u->whereIn('partner_id', $partnerIds))->get()]);
    }

    private function create(GenerateLicenseRequest $request, int $quantity, LicenseGeneratorService $generator, PartnerHierarchyService $hierarchy, ActiveProjectService $activeProjects, ActivityLogger $log): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();
        $project = $activeProjects->get($request, $actor);
        abort_unless($project, 422, 'Selecciona una aplicación.');
        $partnerId = $actor->role === Role::PARTNER ? ($data['partner_id'] ?? $actor->partner_id) : ($data['partner_id'] ?? null);
        $partner = $partnerId ? Partner::findOrFail($partnerId) : null;
        abort_unless(! $partner || ($partner->project_id === $project->id && ($actor->isAdmin() || $hierarchy->contains($actor->partner, $partner))), 403);
        if (isset($data['user_id'])) {
            $client = User::findOrFail($data['user_id']);
            abort_unless($client->project_id === $project->id && ($actor->isAdmin() || ($client->partner_id && in_array($client->partner_id, $hierarchy->descendantIds($actor->partner), true))), 403);
        }
        $expires = $this->expiration($data['expiry_unit'], (int) ($data['expiry_duration'] ?? 0));
        $options = [
            'user_id' => $data['user_id'] ?? null, 'type' => $data['expiry_unit'] === 'lifetime' ? 'lifetime' : 'standard',
            'expires_at' => $expires, 'max_devices' => (int) ($data['max_devices'] ?? 1),
            'mask' => $data['license_mask'], 'subscription' => $data['subscription'], 'note' => $data['note'] ?? null,
            'expiry_unit' => $data['expiry_unit'], 'expiry_duration' => $data['expiry_duration'] ?? null,
            'lowercase' => $request->boolean('lowercase'), 'uppercase' => $request->boolean('uppercase'),
        ];
        if ($quantity > 1000) {
            for ($remaining = $quantity; $remaining > 0; $remaining -= 1000) {
                GenerateLicenseBatch::dispatch($project->id, $partner?->id, min(1000, $remaining), $options);
            }$message = "{$quantity} licencias enviadas a la cola.";
        } else {
            $generator->generate($project, $partner, $quantity, $options);
            $message = "{$quantity} licencia(s) generada(s).";
        }
        $log->log($quantity > 1 ? 'license.bulk_created' : 'license.created', ['quantity' => $quantity, 'queued' => $quantity > 1000], projectId: $project->id, partnerId: $partner?->id);

        return back()->with('success', $message);
    }

    private function expiration(string $unit, int $duration): ?CarbonInterface
    {
        return match ($unit) {
            'seconds' => now()->addSeconds($duration), 'minutes' => now()->addMinutes($duration),
            'hours' => now()->addHours($duration), 'days' => now()->addDays($duration),
            'weeks' => now()->addWeeks($duration), 'months' => now()->addMonths($duration),
            'years' => now()->addYears($duration), 'lifetime' => null,
        };
    }

    public function store(GenerateLicenseRequest $request, LicenseGeneratorService $generator, PartnerHierarchyService $hierarchy, ActiveProjectService $activeProjects, ActivityLogger $log): RedirectResponse
    {
        return $this->create($request, 1, $generator, $hierarchy, $activeProjects, $log);
    }

    public function bulk(BulkGenerateLicenseRequest $request, LicenseGeneratorService $generator, PartnerHierarchyService $hierarchy, ActiveProjectService $activeProjects, ActivityLogger $log): RedirectResponse
    {
        return $this->create($request, (int) $request->validated('quantity'), $generator, $hierarchy, $activeProjects, $log);
    }

    public function update(UpdateLicenseRequest $request, License $license, PartnerHierarchyService $hierarchy, ActivityLogger $log): RedirectResponse
    {
        $data = $request->validated();
        if ($license->status === 'revoked' && $data['status'] !== 'revoked') {
            throw ValidationException::withMessages(['status' => 'Una licencia revocada no puede reactivarse.']);
        }
        if (! empty($data['user_id'])) {
            $client = User::findOrFail($data['user_id']);
            abort_unless($client->project_id === $license->project_id && ($request->user()->isAdmin() || ($client->partner_id && in_array($client->partner_id, $hierarchy->descendantIds($request->user()->partner), true))), 403);
        }
        $license->update([...$data, 'activated_at' => $data['status'] === 'active' ? ($license->activated_at ?? now()) : $license->activated_at]);
        $log->log('license.'.$data['status'], ['license_id' => $license->id], projectId: $license->project_id, partnerId: $license->partner_id);

        return back()->with('success', 'Licencia actualizada.');
    }
}
