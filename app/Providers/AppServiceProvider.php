<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        Relation::morphMap([
            'homeroom_adviser'                => \App\Models\HomeroomAssignment::class,
            'App\\Models\\Club'               => \App\Models\Club::class,
            'App\\Models\\Office'             => \App\Models\Office::class,
            'App\\Models\\Department'         => \App\Models\Department::class,
            'App\\Models\\StudentGovernment'  => \App\Models\StudentGovernment::class,
        ]);
    }
}
