<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\Actions\Data\AbstractData;

final class TestAdminUsersData extends AbstractData
{
    public function __construct(
        public readonly array $users,
    ) {}
}
