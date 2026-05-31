<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Utils\DataObject;

final class TestApiWebData extends AbstractData
{
    public function __construct(
        public readonly DataObject $data,
        public readonly string $message,
    ) {}
}
