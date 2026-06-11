<?php

namespace Scarneros\JapanPostalCodes\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Scarneros\JapanPostalCodes\Facades\PostalCode;
use Scarneros\JapanPostalCodes\JapanPostalCodesServiceProvider;

/**
 * Base test case for the JapanPostalCodes package.
 *
 * Sets up Orchestra Testbench with the service provider and default config.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run the package migration
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            JapanPostalCodesServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'PostalCode' => PostalCode::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Use in‑memory SQLite database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Use default package config
        $app['config']->set('japan-postal-codes.table_name', 'japan_postal_codes');
        $app['config']->set('japan-postal-codes.cache.enabled', false);
    }
}
