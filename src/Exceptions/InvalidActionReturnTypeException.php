<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Exceptions;

use RuntimeException;

final class InvalidActionReturnTypeException extends RuntimeException
{
    public static function create(string $actionClass, string $expected, string $actual): self
    {
        return new self(
            sprintf(
                'Action %s must return %s. Got %s instead.',
                $actionClass,
                $expected,
                $actual
            )
        );
    }

    public static function unionTypeNotAllowed(string $actionClass, string $returnType): self
    {
        return new self(
            sprintf(
                'Action %s cannot use union type %s. Each Action must have a single, unique return type.',
                $actionClass,
                $returnType
            )
        );
    }
}
