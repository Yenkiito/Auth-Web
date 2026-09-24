<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ActivityLogger;
use App\Services\ProjectKeyGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);
        $projects = Project::withCount(['partners', 'users', 'licenses'])->orderBy('name')->get();
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
}
