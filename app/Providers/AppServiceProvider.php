<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Aset baru memaksa muat ulang penuh
        Vite::useScriptTagAttributes(['data-turbo-track' => 'reload']);
        Vite::useStyleTagAttributes(['data-turbo-track' => 'reload']);

        Paginator::defaultView('pagination');
    }
}
