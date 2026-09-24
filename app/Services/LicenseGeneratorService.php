<?php

namespace App\Services;

use App\Models\License;
use App\Models\Partner;
use App\Models\Project;
use Illuminate\Database\QueryException;

class LicenseGeneratorService
{
    private const UPPERCASE = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const LOWERCASE = 'abcdefghijkmnopqrstuvwxyz23456789';

    public function key(string $mask = 'KNYR-****-****-****', bool $lowercase = false, bool $uppercase = true): string
    {
        $alphabet = ($uppercase ? self::UPPERCASE : '').($lowercase ? self::LOWERCASE : '');
        if ($alphabet === '') {
            $alphabet = self::UPPERCASE;
        }

        return preg_replace_callback('/\*/', fn () => $alphabet[random_int(0, strlen($alphabet) - 1)], $mask);
    }

    public function generate(Project $project, ?Partner $partner, int $quantity, array $options = []): array
    {
        $created = [];
        for ($number = 0; $number < $quantity; $number++) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                try {
                    $created[] = License::create([
                        'project_id' => $project->id, 'partner_id' => $partner?->id,
                        'user_id' => $options['user_id'] ?? null,
                        'key' => $this->key($options['mask'] ?? 'KNYR-****-****-****', $options['lowercase'] ?? false, $options['uppercase'] ?? true),
                        'mask' => $options['mask'] ?? 'KNYR-****-****-****',
                        'type' => $options['type'] ?? 'standard',
                        'subscription' => $options['subscription'] ?? 'default',
                        'note' => $options['note'] ?? null,
                        'expiry_unit' => $options['expiry_unit'] ?? 'days',
                        'expiry_duration' => $options['expiry_duration'] ?? null,
                        'status' => $options['status'] ?? 'available',
                        'expires_at' => $options['expires_at'] ?? null, 'max_devices' => $options['max_devices'] ?? 1,
                    ]);
                    break;
                } catch (QueryException $e) {
                    if ($attempt === 4) {
                        throw $e;
                    }
                }
            }
        }

        return $created;
    }
}
