<?php

declare(strict_types=1);

namespace Clicamal\Darauf;

use Clicamal\Darauf\Did\DidResolverDelegator;
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
        $this->app->singleton(DidResolverDelegator::class);

        foreach (config('darauf.didResolvers', []) as $name => $class) {
            $this->app->bind("darauf.didResolvers.{$name}", $class);
        }

        foreach (config('darauf.challengeManagers', []) as $name => $class) {
            foreach (explode('|', $name) as $verificationMethodType) {
                $this->app->bind("darauf.challengeManagers.{$verificationMethodType}", $class);
            }
        }
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
            __DIR__.'/../config/darauf.php' => config_path('darauf.php'),
        ], ['darauf', 'darauf-config']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/darauf'),
        ], ['darauf', 'darauf-lang']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['darauf', 'darauf-migrations']);
    }
}
