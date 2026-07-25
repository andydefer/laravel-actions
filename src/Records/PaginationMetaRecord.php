<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\Sequential;
use AndyDefer\PhpClient\ValueObjects\UrlVO;

final class PaginationMetaRecord extends AbstractRecord
{
    public function __construct(
        public readonly Sequential $items,
        public readonly int $current_page,
        public readonly int $per_page,
        public readonly int $total,
        public readonly int $last_page,
        public readonly ?UrlVO $next_page_url = null,
        public readonly ?UrlVO $prev_page_url = null,
    ) {}
}
