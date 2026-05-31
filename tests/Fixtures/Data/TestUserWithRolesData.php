<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

class TestUserWithRolesData extends AbstractData
{
    public function __construct(
        public readonly array $roles,
    ) {}
}
