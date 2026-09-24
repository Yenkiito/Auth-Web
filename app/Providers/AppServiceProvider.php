<?php

namespace App\Providers;

use App\Enums\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-admins', fn ($user) => $user->role === Role::OWNER);
        Vite::createAssetPathsUsing(fn (string $path): string => '/'.ltrim($path, '/'));
        Vite::prefetch(concurrency: 3);
    }
}
