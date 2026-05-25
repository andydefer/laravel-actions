<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\Actions\Data\AbstractData;

final class TestCastData extends AbstractData
{
    public function __construct(
        public readonly int $castInt,
        public readonly float $castFloat,
        public readonly bool $castBoolTrue,
        public readonly bool $castBoolFalse,
    ) {}
}
