<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class CastParamsData extends AbstractData
{
    public function __construct(
        public readonly int $int,
        public readonly float $float,
        public readonly bool $boolTrue,
        public readonly bool $boolFalse,
        public readonly string $name,
        public readonly string $email,
        public readonly string $id,
    ) {}
}
