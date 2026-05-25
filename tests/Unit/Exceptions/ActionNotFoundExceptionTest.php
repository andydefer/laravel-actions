<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Unit\Exceptions;

use AndyDefer\Actions\Exceptions\ActionNotFoundException;
use AndyDefer\Actions\Tests\UnitTestCase;

final class ActionNotFoundExceptionTest extends UnitTestCase
{
    public function test_create_returns_exception_with_correct_message(): void
    {
        $exception = ActionNotFoundException::create('App\\Actions\\NonExistentAction');

        $this->assertInstanceOf(ActionNotFoundException::class, $exception);
        $this->assertSame(
            'Action App\\Actions\\NonExistentAction not found. Please check the namespace and class name.',
            $exception->getMessage()
        );
    }

    public function test_invalid_class_returns_exception_with_correct_message(): void
    {
        $exception = ActionNotFoundException::invalidClass(
            'App\\Actions\\InvalidAction',
            'AndyDefer\\Actions\\Actions\\AbstractAction'
        );

        $this->assertInstanceOf(ActionNotFoundException::class, $exception);
        $this->assertSame(
            'Class App\\Actions\\InvalidAction must extend AndyDefer\\Actions\\Actions\\AbstractAction.',
            $exception->getMessage()
        );
    }

    public function test_exception_can_be_thrown_and_caught(): void
    {
        $this->expectException(ActionNotFoundException::class);
        $this->expectExceptionMessage('Action TestAction not found');

        throw ActionNotFoundException::create('TestAction');
    }

    public function test_exception_extends_runtime_exception(): void
    {
        $exception = ActionNotFoundException::create('TestAction');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
