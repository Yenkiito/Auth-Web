<?php

use App\Models\ApiSession;
use App\Models\License;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('kenyra:expire-licenses', function () {
    $count = License::whereNotIn('status', ['expired', 'revoked'])->whereNotNull('expires_at')->where('expires_at', '<=', now())->update(['status' => 'expired']);
    $this->info("{$count} licencia(s) marcada(s) como expirada(s).");
})->purpose('Marca licencias vencidas como expiradas');

Artisan::command('kenyra:prune-api-sessions', function () {
    $count = ApiSession::where('expires_at', '<=', now())->delete();
    $this->info("{$count} sesión(es) de API expirada(s) eliminada(s).");
})->purpose('Elimina sesiones expiradas de la API');

Schedule::command('kenyra:expire-licenses')->hourly()->withoutOverlapping();
Schedule::command('kenyra:prune-api-sessions')->daily()->withoutOverlapping();
