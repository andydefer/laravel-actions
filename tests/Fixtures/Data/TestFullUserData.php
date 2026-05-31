<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Abstracts\AbstractData;

class TestFullUserData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly TestUserStatus $status,
        public readonly TestUserRole $role,
        public readonly TestUserGrade $grade,
        public readonly ?string $emailVerifiedAt,
        public readonly string $createdAt,
        public readonly array $tags,
        public readonly array $products,
        public readonly TestProductData $featuredProduct,
        public readonly ?TestFullUserData $child = null,
    ) {}
}
