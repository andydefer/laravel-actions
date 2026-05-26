<?php

declare(strict_types=1);

namespace AndyDefer\Actions;

use Illuminate\Support\ServiceProvider;

class ActionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/actions.php', 'actions');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/actions.php' => config_path('actions.php'),
        ], 'actions-config');
    }
}
