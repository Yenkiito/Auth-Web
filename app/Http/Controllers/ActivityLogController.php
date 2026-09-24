<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Services\PartnerHierarchyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request, PartnerHierarchyService $hierarchy): Response
    {
        $actor = $request->user();
        $q = ActivityLog::query();
        if ($actor->role === Role::CLIENT) {
            $q->where('user_id', $actor->id);
        } elseif (! $actor->isAdmin()) {
            $q->whereIn('partner_id', $hierarchy->descendantIds($actor->partner));
        }

        return Inertia::render('Logs/Index', ['items' => $q->latest('created_at')->paginate(40)]);
    }
}
