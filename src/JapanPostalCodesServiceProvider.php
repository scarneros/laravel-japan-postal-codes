<?php

namespace Scarneros\JapanPostalCodes;

use Illuminate\Support\ServiceProvider;
use Scarneros\JapanPostalCodes\Console\ImportPostalCodesCommand;
use Scarneros\JapanPostalCodes\Console\UpdatePostalCodesCommand;

class JapanPostalCodesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/japan-postal-codes.php',
            'japan-postal-codes',
        );

        $this->app->singleton('japan-postal-codes', fn ($app) => new JapanPostalCodes);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/japan-postal-codes.php' => config_path('japan-postal-codes.php'),
            ], 'japan-postal-codes-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'japan-postal-codes-migrations');

            $this->commands([
                ImportPostalCodesCommand::class,
                UpdatePostalCodesCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (config('japan-postal-codes.api.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
    }
}
