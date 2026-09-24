<?php

namespace App\Services;

use App\Models\Project;

class ProjectKeyGeneratorService
{
    private const OWNER_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

    public function generate(): string
    {
        do {
            $key = 'KNYR-PROJ-'.strtoupper(bin2hex(random_bytes(6)));
        } while (Project::withTrashed()->where('project_key', $key)->exists());

        return $key;
    }

    public function ownerId(): string
    {
        do {
            $value = '';
            for ($i = 0; $i < 10; $i++) {
                $value .= self::OWNER_ALPHABET[random_int(0, strlen(self::OWNER_ALPHABET) - 1)];
            }
        } while (Project::where('owner_id', $value)->exists());

        return $value;
    }

    public function rotate(Project $project): string
    {
        $project->forceFill(['project_key' => $this->generate()])->save();

        return $project->project_key;
    }
}
