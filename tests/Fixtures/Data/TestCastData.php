<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class TestCastData extends AbstractData
{
    public function __construct(
        public readonly int $castInt,
        public readonly float $castFloat,
        public readonly bool $castBoolTrue,
        public readonly bool $castBoolFalse,
    ) {}
}
