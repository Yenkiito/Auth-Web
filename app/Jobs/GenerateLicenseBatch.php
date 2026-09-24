<?php

namespace App\Jobs;

use App\Models\Partner;
use App\Models\Project;
use App\Services\LicenseGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateLicenseBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $projectId, public ?int $partnerId, public int $quantity, public array $options) {}

    public function handle(LicenseGeneratorService $generator): void
    {
        $project = Project::findOrFail($this->projectId);
        $partner = $this->partnerId ? Partner::findOrFail($this->partnerId) : null;
        $generator->generate($project, $partner, $this->quantity, $this->options);
    }
}
