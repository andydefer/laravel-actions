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

    public function test_register_merges_configuration(): void
    {
        // La config par défaut doit être mergée
        $config = Config::get('actions');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('namespace', $config);
        $this->assertArrayHasKey('request_namespace', $config);
        $this->assertArrayHasKey('data_namespace', $config);
        $this->assertArrayHasKey('record_namespace', $config);
    }

    public function test_boot_publishes_configuration(): void
    {
        // Vérifier que la méthode boot ne génère pas d'erreur
        $this->provider->boot();

        $this->addToAssertionCount(1);
    }
}
