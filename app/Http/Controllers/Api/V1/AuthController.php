<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ApiSession;
use App\Models\Device;
use App\Models\License;
use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function init(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ownerid' => ['required', 'string', 'max:20'],
            'version' => ['required', 'string', 'max:20'],
        ]);

        $project = Project::query()
            ->where('name', $data['name'])
            ->where('owner_id', $data['ownerid'])
            ->first();

        if (! $project) {
            return $this->failure('Aplicación no válida.', 401);
        }

        if ($project->status !== 'active') {
            return $this->failure('La aplicación está pausada.', 403);
        }

        if (! hash_equals($project->version, $data['version'])) {
            return $this->failure('Versión no permitida.', 426, ['current_version' => $project->version]);
        }

        $plainToken = Str::random(64);
        $session = ApiSession::create([
            'project_id' => $project->id,
            'token_hash' => hash('sha256', $plainToken),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Aplicación inicializada.',
            'session_token' => $plainToken,
            'expires_at' => $session->expires_at->toIso8601String(),
            'application' => ['name' => $project->name, 'version' => $project->version],
        ]);
    }

    public function login(Request $request, ActivityLogger $logger): JsonResponse
    {
        $data = $request->validate([
            'session_token' => ['nullable', 'string'],
            'username' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string', 'max:255'],
            'hwid' => ['nullable', 'string', 'max:500'],
        ]);
        $session = $this->resolveSession($request);
        $user = User::query()
            ->where('project_id', $session->project_id)
            ->where('role', Role::CLIENT)
            ->where('username', $data['username'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return $this->failure('Usuario o contraseña incorrectos.', 401);
        }

        if (! $user->isActive()) {
            return $this->failure('El usuario está bloqueado.', 403);
        }

        if ($user->expires_at?->isPast()) {
            return $this->failure('El usuario ha expirado.', 403);
        }

        $hwidHash = null;
        if ($user->hwid_affected) {
            if (blank($data['hwid'] ?? null)) {
                throw ValidationException::withMessages(['hwid' => 'El HWID es obligatorio para este usuario.']);
            }
            $hwidHash = $this->hashHwid($data['hwid']);
            if ($user->hwid_hash && ! hash_equals($user->hwid_hash, $hwidHash)) {
                return $this->failure('El HWID no coincide con el dispositivo vinculado.', 403);
            }
            if (! $user->hwid_hash) {
                $user->forceFill(['hwid_hash' => $hwidHash])->save();
            }
        }

        $session->update([
            'user_id' => $user->id,
            'hwid_hash' => $hwidHash,
            'last_seen_at' => now(),
            'expires_at' => now()->addHours(12),
        ]);
        $user->forceFill(['last_login_at' => now()])->save();
        $logger->log('api.login', ['session_id' => $session->id], $user, $session->project_id, $user->partner_id);

        return response()->json([
            'success' => true,
            'message' => 'Usuario autenticado.',
            'expires_at' => $session->expires_at->toIso8601String(),
            'user' => [
                'username' => $user->username,
                'expiration' => $user->expires_at?->toIso8601String(),
                'hwid_affected' => $user->hwid_affected,
            ],
        ]);
    }

    public function license(Request $request, ActivityLogger $logger): JsonResponse
    {
        $data = $request->validate([
            'session_token' => ['nullable', 'string'],
            'license' => ['required', 'string', 'max:255'],
            'hwid' => ['required', 'string', 'max:500'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);
        $session = $this->resolveSession($request, true);
        $hwidHash = $this->hashHwid($data['hwid']);

        if ($session->hwid_hash && ! hash_equals($session->hwid_hash, $hwidHash)) {
            return $this->failure('El HWID no coincide con la sesión.', 403);
        }

        $result = DB::transaction(function () use ($data, $session, $hwidHash) {
            $license = License::query()
                ->where('project_id', $session->project_id)
                ->where('key', $data['license'])
                ->lockForUpdate()
                ->first();

            if (! $license) {
                return ['error' => $this->failure('Licencia no válida.', 401)];
            }
            if (in_array($license->status, ['revoked', 'suspended', 'expired'], true)) {
                return ['error' => $this->failure('La licencia no está activa.', 403)];
            }
            if ($license->expires_at?->isPast()) {
                $license->update(['status' => 'expired']);

                return ['error' => $this->failure('La licencia ha expirado.', 403)];
            }
            if ($license->user_id && $license->user_id !== $session->user_id) {
                return ['error' => $this->failure('La licencia pertenece a otro usuario.', 403)];
            }

            if ($license->status === 'available') {
                $license->update([
                    'user_id' => $session->user_id,
                    'partner_id' => $license->partner_id ?? $session->user->partner_id,
                    'status' => 'active',
                    'activated_at' => now(),
                    'expires_at' => $this->licenseExpiration($license),
                ]);
            }

            $device = Device::query()->where('license_id', $license->id)->where('hwid', $hwidHash)->first();
            if (! $device && $license->devices()->where('status', 'active')->count() >= $license->max_devices) {
                return ['error' => $this->failure('La licencia alcanzó el límite de dispositivos.', 403)];
            }

            $device ??= new Device(['license_id' => $license->id, 'hwid' => $hwidHash, 'first_seen_at' => now()]);
            $device->fill([
                'project_id' => $session->project_id,
                'user_id' => $session->user_id,
                'device_name' => $data['device_name'] ?? null,
                'last_seen_at' => now(),
                'status' => 'active',
            ])->save();
            $session->update(['license_id' => $license->id, 'hwid_hash' => $hwidHash, 'last_seen_at' => now()]);

            return ['license' => $license->fresh(), 'device' => $device];
        });

        if (isset($result['error'])) {
            return $result['error'];
        }

        $license = $result['license'];
        $logger->log('api.license_validated', ['license_id' => $license->id], $session->user, $session->project_id, $session->user->partner_id);

        return response()->json([
            'success' => true,
            'message' => 'Licencia validada.',
            'license' => [
                'status' => $license->status,
                'subscription' => $license->subscription,
                'expires_at' => $license->expires_at?->toIso8601String(),
                'max_devices' => $license->max_devices,
            ],
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => ['nullable', 'string'],
            'hwid' => ['nullable', 'string', 'max:500'],
        ]);
        $session = $this->resolveSession($request, true);
        $user = $session->user;

        if (! $user->isActive() || $user->expires_at?->isPast()) {
            return $this->failure('La cuenta ya no está vigente.', 403);
        }
        if ($session->hwid_hash) {
            if (blank($request->input('hwid'))) {
                throw ValidationException::withMessages(['hwid' => 'El HWID es obligatorio para esta sesión.']);
            }
            if (! hash_equals($session->hwid_hash, $this->hashHwid($request->string('hwid')->toString()))) {
                return $this->failure('El HWID no coincide con la sesión.', 403);
            }
        }

        $license = $session->license;
        if ($license && ($license->status !== 'active' || $license->expires_at?->isPast())) {
            return $this->failure('La licencia ya no está vigente.', 403);
        }

        $session->update(['last_seen_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Sesión válida.',
            'authenticated' => true,
            'licensed' => (bool) $license,
            'expires_at' => $session->expires_at->toIso8601String(),
        ]);
    }

    private function resolveSession(Request $request, bool $authenticated = false): ApiSession
    {
        $plainToken = $request->bearerToken() ?: $request->input('session_token');
        if (! is_string($plainToken) || $plainToken === '') {
            abort(response()->json(['success' => false, 'message' => 'Falta el token de sesión.'], 401));
        }

        $session = ApiSession::with(['project', 'user', 'license'])
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('expires_at', '>', now())
            ->first();

        if (! $session || $session->project->status !== 'active') {
            abort(response()->json(['success' => false, 'message' => 'La sesión no es válida o expiró.'], 401));
        }
        if ($authenticated && ! $session->user) {
            abort(response()->json(['success' => false, 'message' => 'Primero debes iniciar sesión.'], 401));
        }

        return $session;
    }

    private function licenseExpiration(License $license): ?Carbon
    {
        if ($license->expiry_unit === 'lifetime') {
            return null;
        }

        $duration = max(1, (int) $license->expiry_duration);

        return match ($license->expiry_unit) {
            'seconds' => now()->addSeconds($duration),
            'minutes' => now()->addMinutes($duration),
            'hours' => now()->addHours($duration),
            'weeks' => now()->addWeeks($duration),
            'months' => now()->addMonths($duration),
            'years' => now()->addYears($duration),
            default => now()->addDays($duration),
        };
    }

    private function hashHwid(string $hwid): string
    {
        return hash('sha256', trim($hwid));
    }

    private function failure(string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, ...$extra], $status);
    }
}
