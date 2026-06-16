<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Greenhouse;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
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
        Paginator::useBootstrapFive();

        // Share sidebar data (greenhouse list + active alert count) with the master layout.
        View::composer('layouts.app', function ($view) {
            $view->with('sidebarGreenhouses', Greenhouse::orderBy('name')->get());
            $view->with('sidebarAlertCount', Alert::where('status', 'active')->count());
        });
    }
}
