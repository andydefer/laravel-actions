<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class CastWebRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $int,
        public readonly float $float,
        public readonly bool $boolTrue,
        public readonly bool $boolFalse,
    ) {}
}
