<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale', 'fr'));

        // Accès administrateur : utilisé par le middleware « can:admin » et @can('admin') dans les vues.
        Gate::define('admin', fn (User $user) => $user->isAdmin() && $user->is_active);
    }
}
