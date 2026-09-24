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

        $id = $request->session()->get('active_project_id');
        $project = $id ? Project::find($id) : null;
        if (! $project) {
            $project = Project::orderBy('name')->first();
            if ($project) {
                $request->session()->put('active_project_id', $project->id);
            }
        }

        return $project;
    }
}
