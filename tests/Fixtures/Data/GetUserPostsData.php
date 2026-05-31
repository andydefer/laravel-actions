<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class GetUserPostsData extends AbstractData
{
    public function __construct(
        public readonly int $userId,
        public readonly int $postId,
        public readonly string $message,
    ) {}
}
