<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

class TestProductData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int|float $price,
        public readonly ?bool $isFeatured = false,
        public readonly ?string $createdAt = null,
    ) {}
}
