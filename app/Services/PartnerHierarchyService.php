<?php

namespace App\Services;

use App\Models\Partner;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PartnerHierarchyService
{
    public function descendantIds(Partner $partner, bool $includeSelf = true): array
    {
        $ids = $includeSelf ? [$partner->id] : [];
        $frontier = [$partner->id];
        while ($frontier !== []) {
            $frontier = Partner::where('project_id', $partner->project_id)->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return array_values(array_unique($ids));
    }

    public function descendants(Partner $partner): Collection
    {
        return Partner::whereIn('id', $this->descendantIds($partner, false))->get();
    }

    public function contains(Partner $root, Partner $candidate): bool
    {
        return $root->project_id === $candidate->project_id && in_array($candidate->id, $this->descendantIds($root), true);
    }

    public function assertValidParent(Partner $partner, ?Partner $parent): void
    {
        if (! $parent) {
            return;
        }
        if ($partner->id === $parent->id || $partner->project_id !== $parent->project_id || $this->contains($partner, $parent)) {
            throw ValidationException::withMessages(['parent_id' => 'La asignación crearía un ciclo o cruzaría proyectos.']);
        }
    }
}
