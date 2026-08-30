<?php

declare(strict_types=1);

namespace Clicamal\Darauf;

use Illuminate\Support\ServiceProvider;

class DaraufServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/darauf.php', 'darauf');

        $this->app->singleton(Darauf::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/darauf.php');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'darauf');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/darauf'),
        ], ['darauf', 'darauf-lang']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['darauf', 'darauf-migrations']);
    }
}
