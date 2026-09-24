<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function log(string $action, array $metadata = [], ?User $actor = null, ?int $projectId = null, ?int $partnerId = null): ActivityLog
    {
        $actor ??= auth()->user();
        $request = app(Request::class);
        unset($metadata['password'], $metadata['password_confirmation'], $metadata['project_key'], $metadata['key']);

        return ActivityLog::create([
            'actor' => $actor?->username ?? 'system', 'project_id' => $projectId ?? $actor?->project_id,
            'partner_id' => $partnerId ?? $actor?->partner_id, 'user_id' => $actor?->id,
            'action' => $action, 'ip' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'metadata' => $metadata ?: null, 'created_at' => now(),
        ]);
    }
}
