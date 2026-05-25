<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base TestCase for all Unit tests.
 *
 * Unit tests should be fast, isolated, and not depend on Laravel's container.
 * Use mocks for external dependencies.
 *
 * @author Andy Defer
 */
abstract class UnitTestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }
}
