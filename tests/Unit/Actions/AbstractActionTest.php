<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Unit\Actions;

use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Actions\TestAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\TestActionWithHooks;
use AndyDefer\Actions\Tests\Fixtures\Records\TestApiRecord;
use AndyDefer\DomainStructures\Utils\EmptyRecord;
use Exception;
use PHPUnit\Framework\TestCase;

final class AbstractActionTest extends TestCase
{
    public function test_action_returns_response_factory(): void
    {
        // Arrange
        $action = new TestAction;
        $request = new TestApiRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );

        // Act
        $result = $action->run($request);

        // Assert
        $this->assertInstanceOf(ResponseFactory::class, $result);
        $this->assertEquals('json', $result->getType()->value);
        $this->assertEquals(200, $result->getStatus());
    }

    public function test_action_calls_before_hook(): void
    {
        // Arrange
        $action = new TestActionWithHooks;
        $request = new TestApiRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );

        // Act
        $action->run($request);

        // Assert
        $this->assertTrue($action->beforeCalled);
        $this->assertTrue($action->afterCalled);
        $this->assertTrue($action->afterSuccess);
    }

    public function test_action_calls_after_hook_with_error_on_exception(): void
    {
        // Arrange
        $action = new TestActionWithHooks;
        $request = new TestApiRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );

        $action->shouldThrow = true;

        // Act & Assert
        $this->expectException(Exception::class);

        try {
            $action->run($request);
        } finally {
            $this->assertTrue($action->beforeCalled);
            $this->assertTrue($action->afterCalled);
            $this->assertFalse($action->afterSuccess);
            $this->assertNotNull($action->afterError);
        }
    }

    public function test_action_can_get_request(): void
    {
        // Arrange
        $action = new TestAction;
        $request = new TestApiRecord(
            id: 123,
            name: 'John Doe',
            email: 'john@example.com',
        );

        // Act
        $action->run($request);
        /** @var TestApiRecord $retrievedRequest */
        $retrievedRequest = $action->getRecordRequest();

        // Assert
        $this->assertSame(123, $retrievedRequest->id);
        $this->assertSame('John Doe', $retrievedRequest->name);
        $this->assertSame('john@example.com', $retrievedRequest->email);
    }

    public function test_action_returns_empty_record_when_not_set(): void
    {
        // Arrange
        $action = new TestAction;

        // Act
        $action->run(new EmptyRecord);

        // Assert
        $this->assertInstanceOf(EmptyRecord::class, $action->getRecordRequest());
    }

    public function test_action_returns_response_factory_with_correct_data(): void
    {
        // Arrange
        $action = new TestAction;
        $request = new TestApiRecord(
            id: 5,
            name: 'Jane Doe',
            email: 'jane@example.com',
        );

        // Act
        $result = $action->run($request);
        $data = $result->getContent()->toArray();

        // Assert
        $this->assertSame('5', $data['id']);
        $this->assertSame('Jane Doe', $data['name']);
        $this->assertSame('jane@example.com', $data['email']);
        $this->assertSame('active', $data['status']);
        $this->assertSame('user', $data['role']);
        $this->assertSame(1, $data['grade']);
        $this->assertIsArray($data['tags']);
        $this->assertIsString($data['createdAt']);
    }

    public function test_action_handles_nullable_values(): void
    {
        // Arrange
        $action = new TestAction;
        $request = new TestApiRecord(
            id: null,
            name: null,
            email: null,
        );

        // Act
        $result = $action->run($request);
        $data = $result->getContent()->toArray();

        // Assert
        $this->assertSame('1', $data['id']);
        $this->assertSame('Test User 1', $data['name']);
        $this->assertSame('test1@example.com', $data['email']);
    }
}
