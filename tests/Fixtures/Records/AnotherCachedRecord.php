<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class AnotherCachedRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $label,
    ) {}
}
