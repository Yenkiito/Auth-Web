<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreManagerRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ManagerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Managers/Index', [
            'items' => User::query()
                ->where('role', Role::ADMIN)
                ->withCount('managedProjects')
                ->latest()
                ->paginate(25),
        ]);
    }

    public function store(StoreManagerRequest $request, ActivityLogger $log): RedirectResponse
    {
        $data = $request->validated();
        $manager = User::create([
            ...$data,
            'email' => $data['email'] ?: null,
            'role' => Role::ADMIN,
            'status' => 'active',
        ]);
        $log->log('manager.created', ['manager_id' => $manager->id]);

        return back()->with('success', 'Manager creado. Ya puede crear sus propias aplicaciones y socios.');
    }

    public function destroy(Request $request, User $manager, ActivityLogger $log): RedirectResponse
    {
        abort_unless($request->user()->role === Role::OWNER && $manager->role === Role::ADMIN, 403);
        if ($manager->managedProjects()->exists()) {
            return back()->with('error', 'No se puede eliminar un manager que todavía tiene aplicaciones.');
        }

        $log->log('manager.deleted', ['manager_id' => $manager->id, 'username' => $manager->username]);
        DB::table('sessions')->where('user_id', $manager->id)->delete();
        $manager->delete();

        return back()->with('success', 'Manager eliminado.');
    }
}
