<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class TestCookieData extends AbstractData
{
    public function __construct(
        public readonly ?string $preference,
    ) {}
}
