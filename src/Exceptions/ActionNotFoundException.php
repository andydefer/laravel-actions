<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Exceptions;

use RuntimeException;

final class ActionNotFoundException extends RuntimeException
{
    public static function create(string $actionClass): self
    {
        return new self(
            sprintf(
                'Action %s not found. Please check the namespace and class name.',
                $actionClass
            )
        );
    }

    public static function invalidClass(string $actionClass, string $expected): self
    {
        return new self(
            sprintf(
                'Class %s must extend %s.',
                $actionClass,
                $expected
            )
        );
    }
}
