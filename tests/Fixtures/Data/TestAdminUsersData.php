<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Collections\Utility\DataObjectCollection;

final class TestAdminUsersData extends AbstractData
{
    public function __construct(
        public readonly DataObjectCollection $users,
    ) {}
}
