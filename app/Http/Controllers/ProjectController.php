<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\ApiSession;
use App\Models\Device;
use App\Models\License;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ProjectKeyGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);
        $projects = Project::query()
            ->when($request->user()->isManager(), fn ($query) => $query->where('manager_id', $request->user()->id))
            ->withCount(['partners', 'users', 'licenses'])->orderBy('name')->get();
        $selected = $projects->firstWhere('id', (int) $request->session()->get('active_project_id')) ?? $projects->first();
        if ($selected) {
            $request->session()->put('active_project_id', $selected->id);
        }

        return Inertia::render('Projects/Index', [
            'items' => $projects,
            'selected' => $selected ? [
                ...$selected->makeVisible('project_key')->toArray(),
                'application_secret' => $selected->project_key,
                'api_url' => rtrim(config('app.url'), '/').'/api/v1/',
            ] : null,
        ]);
    }

    public function store(StoreProjectRequest $request, ProjectKeyGeneratorService $keys, ActivityLogger $log): RedirectResponse
    {
        $data = $request->validated();
        $baseSlug = Str::slug($data['name']) ?: 'application';
        $slug = $baseSlug;
        for ($suffix = 2; Project::where('slug', $slug)->exists(); $suffix++) {
            $slug = $baseSlug.'-'.$suffix;
        }
        $project = Project::create([
            'manager_id' => $request->user()->role === Role::ADMIN ? $request->user()->id : null,
            'name' => $data['name'], 'slug' => $slug, 'key_prefix' => $data['key_prefix'], 'description' => $data['description'] ?? null,
            'version' => $data['version'] ?? '1.0', 'status' => $data['status'] ?? 'active',
            'owner_id' => $keys->ownerId(), 'project_key' => $keys->generate(),
        ]);
        $request->session()->put('active_project_id', $project->id);
        $log->log('project.created', ['project_id' => $project->id], projectId: $project->id);

        return back()->with('success', 'Proyecto creado.');
    }

    public function select(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);
        $request->session()->put('active_project_id', $project->id);

        return back()->with('success', "Aplicación {$project->name} seleccionada.");
    }

    public function toggle(Request $request, Project $project, ActivityLogger $log): RedirectResponse
    {
        $this->authorize('update', $project);
        $project->update(['status' => $project->status === 'active' ? 'inactive' : 'active']);
        $log->log('project.status_changed', ['project_id' => $project->id, 'status' => $project->status], projectId: $project->id);

        return back()->with('success', 'Estado de la aplicación actualizado.');
    }

    public function update(UpdateProjectRequest $request, Project $project, ActivityLogger $log): RedirectResponse
    {
        $project->update($request->validated());
        $log->log('project.updated', ['project_id' => $project->id], projectId: $project->id);

        return back()->with('success', 'Proyecto actualizado.');
    }

    public function rotate(Request $request, Project $project, ProjectKeyGeneratorService $keys, ActivityLogger $log): RedirectResponse
    {
        $this->authorize('update', $project);
        $keys->rotate($project);
        $log->log('project.key_rotated', ['project_id' => $project->id], projectId: $project->id);

        return back()->with('success', 'Project Key regenerada.');
    }

    public function reveal(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        return back()->with('project_key', $project->project_key);
    }

    public function destroy(Request $request, Project $project, ActivityLogger $log): RedirectResponse
    {
        $this->authorize('delete', $project);
        $projectId = $project->id;
        $projectName = $project->name;

        DB::transaction(function () use ($project) {
            $userIds = User::where('project_id', $project->id)->pluck('id');
            DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            ApiSession::where('project_id', $project->id)->delete();
            Device::where('project_id', $project->id)->delete();
            License::where('project_id', $project->id)->delete();
            Partner::where('project_id', $project->id)->delete();
            User::where('project_id', $project->id)->update([
                'status' => 'blocked',
                'remember_token' => null,
            ]);
            $project->delete();
        });

        if ((int) $request->session()->get('active_project_id') === $projectId) {
            $request->session()->forget('active_project_id');
        }
        $log->log('project.deleted', ['project_id' => $projectId, 'project_name' => $projectName]);

        return redirect()->route('admin.projects.index')->with('success', 'Aplicación eliminada y accesos asociados bloqueados.');
    }
}
