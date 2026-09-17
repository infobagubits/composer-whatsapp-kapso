<?php

declare(strict_types=1);

namespace Integrations\Kapso;

use Illuminate\Support\ServiceProvider;
use Integrations\Kapso\Console\Commands\KapsoCommand;

class KapsoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-kapso.php', 'laravel-kapso');

        $this->app->singleton(Kapso::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/laravel-kapso.php');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-kapso.php' => config_path('laravel-kapso.php'),
        ], ['laravel-kapso', 'laravel-kapso-config']);

        $this->commands([
            KapsoCommand::class,
        ]);
    }
}
