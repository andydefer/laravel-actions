<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\DataObject;

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
        public readonly ?DataObject $query = new DataObject,
        public readonly ?DataObject $input = new DataObject,
        public readonly ?DataObject $cookie = new DataObject,
        public readonly ?DataObject $session = new DataObject,
        public readonly bool $ajax = false,
    ) {}
}
