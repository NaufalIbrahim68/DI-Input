<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Daftarkan komponen Blade kustom
        Blade::component('summary-card', \App\View\Components\SummaryCard::class);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
