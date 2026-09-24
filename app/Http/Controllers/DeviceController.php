<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreDeviceRequest;
use App\Models\Device;
use App\Models\License;
use App\Models\User;
use App\Services\ActiveProjectService;
use App\Services\ActivityLogger;
use App\Services\PartnerHierarchyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    public function index(Request $request, PartnerHierarchyService $hierarchy, ActiveProjectService $activeProjects): Response
    {
        $this->authorize('viewAny', Device::class);
        $actor = $request->user();
        $activeProject = $activeProjects->get($request, $actor);
        $q = Device::with(['project:id,name', 'user:id,username', 'license:id,key,partner_id']);
        if ($actor->role === Role::CLIENT) {
            $q->where('user_id', $actor->id);
        } elseif ($actor->isAdmin()) {
            $q->where('project_id', $activeProject?->id);
        } else {
            $q->whereHas('license', fn ($l) => $l->whereIn('partner_id', $hierarchy->descendantIds($actor->partner)));
        }

        $licenses = License::with('user:id,username')->whereNotNull('user_id');
        if ($actor->isAdmin()) {
            $licenses->where('project_id', $activeProject?->id);
        } elseif ($actor->role === Role::PARTNER) {
            $licenses->whereIn('partner_id', $hierarchy->descendantIds($actor->partner));
        } elseif ($actor->role === Role::CLIENT) {
            $licenses->whereRaw('1 = 0');
        }

        return Inertia::render('Devices/Index', ['items' => $q->latest()->paginate(25), 'licenses' => $licenses->select('id', 'key', 'user_id', 'project_id', 'max_devices')->get()]);
    }

    public function store(StoreDeviceRequest $request, ActivityLogger $log): RedirectResponse
    {
        $data = $request->validated();
        $license = License::findOrFail($data['license_id']);
        $this->authorize('update', $license);
        $user = User::findOrFail($data['user_id']);
        abort_unless($license->user_id === $user->id && $license->project_id === $user->project_id, 422, 'La licencia y el cliente no coinciden.');
        abort_if($license->devices()->where('status', 'active')->count() >= $license->max_devices, 422, 'La licencia alcanzó max_devices.');
        $device = Device::create([...$data, 'project_id' => $license->project_id, 'first_seen_at' => now(), 'last_seen_at' => now()]);
        $log->log('device.created', ['device_id' => $device->id], projectId: $device->project_id, partnerId: $license->partner_id);

        return back()->with('success', 'Dispositivo registrado.');
    }

    public function update(Request $request, Device $device, ActivityLogger $log): RedirectResponse
    {
        $this->authorize('update', $device);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'blocked'])]]);
        $device->update($data);
        $log->log('device.'.$data['status'], ['device_id' => $device->id], projectId: $device->project_id);

        return back()->with('success', 'Dispositivo actualizado.');
    }

    public function destroy(Request $request, Device $device, ActivityLogger $log): RedirectResponse
    {
        $this->authorize('delete', $device);
        $projectId = $device->project_id;
        $device->delete();
        $log->log('device.reset', ['device_id' => $device->id], projectId: $projectId);

        return back()->with('success', 'Dispositivo eliminado.');
    }
}
