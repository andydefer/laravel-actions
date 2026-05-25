<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\Actions\Data\AbstractData;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserStatus;

class TestUserData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly TestUserStatus $status,
        public readonly TestUserRole $role,
        public readonly TestUserGrade $grade,
        public readonly ?string $emailVerifiedAt,
        public readonly array $tags,
        public readonly string $createdAt,
    ) {}
}
