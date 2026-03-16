<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL; // 1. Add this import
use Illuminate\Support\Facades\View;

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
        View::addNamespace('layouts', resource_path('views/components/layouts'));

        // 2. Force HTTPS if the environment is production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Relation::morphMap([
            'homeroom_adviser'                => \App\Models\HomeroomAssignment::class,
            'App\\Models\\Club'               => \App\Models\Club::class,
            'App\\Models\\Office'             => \App\Models\Office::class,
            'App\\Models\\Department'         => \App\Models\Department::class,
            'App\\Models\\StudentGovernment'  => \App\Models\StudentGovernment::class,
        ]);
    }
}