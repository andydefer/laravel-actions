<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Records\Cars;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class IndexCarsRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $current_page = 1,
        public readonly int $per_page = 5,
    ) {}
}
