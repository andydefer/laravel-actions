<?php

declare(strict_types=1);

namespace AndyDefer\Actions;

use AndyDefer\Actions\Directives\MakeActionDirective;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use Illuminate\Support\ServiceProvider;

class ActionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/actions.php', 'actions');

        // Enregistrer la directive MakeActionDirective
        $this->app->singleton(MakeActionDirective::class, function ($app) {
            return new MakeActionDirective(
                $app->make(DirectiveInteractionService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/actions.php' => config_path('actions.php'),
        ], 'actions-config');
    }
}
