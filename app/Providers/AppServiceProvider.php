<?php

namespace App\Providers;

use App\Models\User;
use App\Services\CohortContext;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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

        // Cohorte affichée (en-tête) et liste du sélecteur pour l'administrateur.
        View::composer('layouts.app', function ($view) {
            $context = app(CohortContext::class);
            $view->with('currentCohort', $context->current());
            $view->with('cohortChoices', auth()->user()?->isAdmin() ? $context->choices() : collect());
        });

        // Accès administrateur : utilisé par le middleware « can:admin » et @can('admin') dans les vues.
        Gate::define('admin', fn (User $user) => $user->isAdmin() && $user->is_active);
    }
}
