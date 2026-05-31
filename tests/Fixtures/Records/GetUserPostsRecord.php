<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class GetUserPostsRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $userId,
        public readonly int $postId,
    ) {}
}
