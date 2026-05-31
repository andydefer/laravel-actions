<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class TestUploadData extends AbstractData
{
    public function __construct(
        public readonly string $message,
    ) {}
}
