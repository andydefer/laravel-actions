<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\Actions\Data\AbstractData;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserRole;

class TestUserWithRolesData extends AbstractData
{
    public function __construct(
        public readonly array $roles,
    ) {}
}
