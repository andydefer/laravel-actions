<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests;

use AndyDefer\Actions\ActionServiceProvider;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base TestCase for all Integration tests.
 *
 * Integration tests boot Laravel and test the package in a real application context.
 * Use these tests for HTTP endpoints, service container resolution, and full-stack flows.
 *
 * @author Andy Defer
 */
abstract class IntegrationTestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ActionServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Laisser vide - la configuration par défaut du package sera mergée
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Reset routes before each test
        Route::clearResolvedInstances();
        $this->app['router']->getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }
}
