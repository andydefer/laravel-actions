<?php

declare(strict_types=1);

namespace AndyDefer\Actions;

use Illuminate\Support\ServiceProvider;

/**
 * Laravel service provider for the Actions package.
 *
 * This service provider handles the registration and bootstrapping of the
 * Actions package within a Laravel application. It manages configuration
 * publishing and merging.
 *
 * @example
 * // Register the provider in config/app.php
 * 'providers' => [
 *     // ...
 *     AndyDefer\Actions\ActionServiceProvider::class,
 * ];
 *
 * // Publish configuration
 * php artisan vendor:publish --tag=actions-config
 *
 * @author Andy Defer
 */
final class ActionServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * This method is called by Laravel during the service registration phase.
     * It merges the package's configuration file with the application's
     * existing configuration.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     *
     * This method is called by Laravel after all service providers have been
     * registered. It sets up configuration publishing so users can customize
     * the package's behavior.
     */
    public function boot(): void {}
}
