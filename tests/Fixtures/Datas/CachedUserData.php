<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Datas;

use AndyDefer\Actions\Tests\Fixtures\ValueObjects\CachedEmailVO;
use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class CachedUserData extends AbstractData
{
    public function __construct(
        public readonly string $name,
        public readonly CachedEmailVO $email,
    ) {}
}
