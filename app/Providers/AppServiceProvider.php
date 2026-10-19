<?php

namespace App\Providers;

use App\Models\CanalAyuda;
use Illuminate\Support\Facades\Schema;
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
        // Compatible con servidores MySQL/MariaDB que limitan los índices a 1000 bytes.
        Schema::defaultStringLength(191);

        View::composer(['publico.index', 'partials.publico.footer'], function ($view): void {
            $canalesAyuda = Schema::hasTable('canales_ayuda') ? CanalAyuda::query()->first() : null;
            $view->with('canalesAyuda', $canalesAyuda);
        });
    }
}
