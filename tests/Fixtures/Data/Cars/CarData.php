<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data\Cars;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class CarData extends AbstractData
{
    public function __construct(
        public readonly int $id,
        public readonly string $brand,
        public readonly string $model,
        public readonly int $year,
        public readonly string $color,
        public readonly float $price,
        public readonly bool $is_available,
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
    ) {}
}
