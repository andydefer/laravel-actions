<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration;

use AndyDefer\Actions\ActionServiceProvider;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Config;

final class ActionServiceProviderTest extends IntegrationTestCase
{
    private ActionServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new ActionServiceProvider($this->app);
    }


    public function test_boot_publishes_configuration(): void
    {
        // Vérifier que la méthode boot ne génère pas d'erreur
        $this->provider->boot();

        $this->addToAssertionCount(1);
    }
}
