<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Services\ActiveProjectService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $activeProject = $user ? app(ActiveProjectService::class)->get($request, $user) : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'applicationContext' => [
                'active' => $activeProject?->only(['id', 'name', 'status']),
                'projects' => $user?->isAdmin()
                    ? fn () => Project::query()
                        ->when($user->isManager(), fn ($projects) => $projects->where('manager_id', $user->id))
                        ->orderBy('name')->get(['id', 'name', 'status'])
                    : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'project_key' => fn () => $request->session()->get('project_key'),
            ],
        ];
    }
}
