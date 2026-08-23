<?php

declare(strict_types=1);

namespace Clicamal\Darauf;

use Clicamal\Darauf\Console\Commands\DaraufCommand;
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

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'darauf');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'darauf');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/darauf.php' => config_path('darauf.php'),
        ], ['darauf', 'darauf-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/darauf'),
        ], ['darauf', 'darauf-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/darauf'),
        ], ['darauf', 'darauf-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/darauf'),
        ], ['darauf', 'darauf-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['darauf', 'darauf-migrations']);

        $this->commands([
            DaraufCommand::class,
        ]);
    }
}
