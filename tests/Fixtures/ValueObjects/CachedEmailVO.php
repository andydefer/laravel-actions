<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use InvalidArgumentException;

final class CachedEmailVO extends AbstractValueObject
{
    public function __construct(
        public readonly string $value,
    ) {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
