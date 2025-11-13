<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
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
    public function boot(): void  // ← QUITA el "function" duplicado
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\RegistrarAusenciasDiarias::class,
                \App\Console\Commands\TestScheduler::class,
            ]);
        }
    }
}