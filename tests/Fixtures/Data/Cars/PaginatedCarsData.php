<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data\Cars;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Utils\Sequential;

final class PaginatedCarsData extends AbstractData
{
    public function __construct(
        public readonly Sequential $items,
        public readonly int $current_page,
        public readonly int $per_page,
        public readonly int $total,
        public readonly int $last_page,
        public readonly ?string $next_page_url = null,
        public readonly ?string $prev_page_url = null,
    ) {}
}
