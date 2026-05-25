<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Unit\Exceptions;

use AndyDefer\Actions\Exceptions\InvalidActionReturnTypeException;
use AndyDefer\Actions\Tests\UnitTestCase;

final class InvalidActionReturnTypeExceptionTest extends UnitTestCase
{
    public function test_create_returns_exception_with_correct_message(): void
    {
        $exception = InvalidActionReturnTypeException::create(
            'App\\Actions\\ShowUserAction',
            'JsonResponse',
            'array'
        );

        $this->assertInstanceOf(InvalidActionReturnTypeException::class, $exception);
        $this->assertSame(
            'Action App\\Actions\\ShowUserAction must return JsonResponse. Got array instead.',
            $exception->getMessage()
        );
    }

    public function test_union_type_not_allowed_returns_exception_with_correct_message(): void
    {
        $exception = InvalidActionReturnTypeException::unionTypeNotAllowed(
            'App\\Actions\\UserAction',
            'JsonResponse|InertiaResponse'
        );

        $this->assertInstanceOf(InvalidActionReturnTypeException::class, $exception);
        $this->assertSame(
            'Action App\\Actions\\UserAction cannot use union type JsonResponse|InertiaResponse. Each Action must have a single, unique return type.',
            $exception->getMessage()
        );
    }

    public function test_exception_can_be_thrown_and_caught(): void
    {
        $this->expectException(InvalidActionReturnTypeException::class);
        $this->expectExceptionMessage('Action TestAction must return JsonResponse. Got string instead.');

        throw InvalidActionReturnTypeException::create('TestAction', 'JsonResponse', 'string');
    }

    public function test_exception_extends_runtime_exception(): void
    {
        $exception = InvalidActionReturnTypeException::create('TestAction', 'JsonResponse', 'array');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function test_create_with_different_return_types(): void
    {
        // Test avec InertiaResponse
        $exception1 = InvalidActionReturnTypeException::create(
            'App\\Actions\\DashboardAction',
            'InertiaResponse',
            'RedirectResponse'
        );

        $this->assertStringContainsString('InertiaResponse', $exception1->getMessage());
        $this->assertStringContainsString('RedirectResponse', $exception1->getMessage());

        // Test avec Response
        $exception2 = InvalidActionReturnTypeException::create(
            'App\\Actions\\DownloadAction',
            'Response',
            'null'
        );

        $this->assertStringContainsString('Response', $exception2->getMessage());
        $this->assertStringContainsString('null', $exception2->getMessage());
    }

    public function test_union_type_not_allowed_with_multiple_types(): void
    {
        $exception = InvalidActionReturnTypeException::unionTypeNotAllowed(
            'App\\Actions\\FlexibleAction',
            'JsonResponse|InertiaResponse|RedirectResponse'
        );

        $this->assertStringContainsString('FlexibleAction', $exception->getMessage());
        $this->assertStringContainsString('JsonResponse|InertiaResponse|RedirectResponse', $exception->getMessage());
    }
}
