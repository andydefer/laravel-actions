<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class TestFormData extends AbstractData
{
    public function __construct(
        public readonly string $submittedName,
        public readonly string $submittedEmail,
    ) {}
}
