<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ActiveProjectService
{
    public function get(Request $request, User $user): ?Project
    {
        if (! $user->isAdmin()) {
            return $user->project;
        }

        $query = Project::query()->when($user->isManager(), fn ($projects) => $projects->where('manager_id', $user->id));
        $id = $request->session()->get('active_project_id');
        $project = $id ? (clone $query)->find($id) : null;
        if (! $project) {
            $project = $query->orderBy('name')->first();
            if ($project) {
                $request->session()->put('active_project_id', $project->id);
            }
        }

        return $project;
    }
}
