<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Records;

use AndyDefer\Records\AbstractRecord;

final class TestWebRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $uri,
        public readonly string $method,
        public readonly ?int $id = null,
        public readonly ?int $userId = null,
        public readonly ?int $castInt = null,
        public readonly ?float $castFloat = null,
        public readonly ?bool $castBoolTrue = null,
        public readonly ?bool $castBoolFalse = null,
        public readonly array $query = [],
        public readonly array $input = [],
        public readonly array $cookie = [],
        public readonly array $session = [],
        public readonly bool $ajax = false,
    ) {}
}
