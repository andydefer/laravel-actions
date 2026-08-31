<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data\Users;

use AndyDefer\Actions\Tests\Fixtures\Collections\CarDataCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class UserWithCarsData extends AbstractData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?CarDataCollection $cars = null,
        public readonly ?CarDataCollection $carsInOtherForm = null,
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
    ) {}
}
