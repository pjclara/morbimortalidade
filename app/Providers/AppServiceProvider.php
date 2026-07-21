<?php

namespace App\Providers;

use App\Services\MenuService;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
        Inertia::share([
            'auth' => fn() => [
                'user' => auth()->user(),
            ],

            'menu' => fn() =>
            auth()->check()
                ? MenuService::getMenuForUser(auth()->user()->load('roles'))
                : [],

        ]);
    }
}
