<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\Actions\Data\AbstractData;

final class TestApiWebData extends AbstractData
{
    public function __construct(
        public readonly array $data,
        public readonly string $message,
    ) {}
}
