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

/**
 * Unit tests for the AbstractAction base class.
 *
 * Verifies the template method lifecycle, hook execution, and request handling.
 */
final class AbstractActionTest extends TestCase
{
    public function test_action_returns_response_factory(): void
    {
        // Arrange: Create action and request record
        $action = new TestAction;
        $request = new TestApiRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );

        // Act: Execute the action
        $result = $action->run($request);

        // Assert: Verify response type and configuration
        $this->assertInstanceOf(ResponseFactory::class, $result);
        $this->assertEquals('json', $result->getType()->value);
        $this->assertEquals(200, $result->getStatus());
    }

    public function test_action_calls_before_hook(): void
    {
        // Arrange: Create action with hook tracking
        $action = new TestActionWithHooks;
        $request = new TestApiRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );

        // Act: Execute the action
        $action->run($request);

        // Assert: Verify both before and after hooks were called with success
        $this->assertTrue($action->beforeCalled);
        $this->assertTrue($action->afterCalled);
        $this->assertTrue($action->afterSuccess);
    }

    public function test_action_calls_after_hook_with_error_on_exception(): void
    {
        // Arrange: Create action configured to throw an exception
        $action = new TestActionWithHooks;
        $request = new TestApiRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );
        $action->shouldThrow = true;

        // Act & Assert: Expect exception and verify hook state
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
        // Arrange: Create action with specific request data
        $action = new TestAction;
        $request = new TestApiRecord(
            id: 123,
            name: 'John Doe',
            email: 'john@example.com',
        );

        // Act: Execute action and retrieve the stored request
        $action->run($request);
        /** @var TestApiRecord $retrievedRequest */
        $retrievedRequest = $action->getRecordRequest();

        // Assert: Verify request data was preserved correctly
        $this->assertSame(123, $retrievedRequest->id);
        $this->assertSame('John Doe', $retrievedRequest->name);
        $this->assertSame('john@example.com', $retrievedRequest->email);
    }

    public function test_action_returns_empty_record_when_not_set(): void
    {
        // Arrange: Create action without explicit request
        $action = new TestAction;

        // Act: Execute with empty record
        $action->run(new EmptyRecord);

        // Assert: Verify empty record was used
        $this->assertInstanceOf(EmptyRecord::class, $action->getRecordRequest());
    }

    public function test_action_returns_response_factory_with_correct_data(): void
    {
        // Arrange: Create action with test data
        $action = new TestAction;
        $request = new TestApiRecord(
            id: 5,
            name: 'Jane Doe',
            email: 'jane@example.com',
        );

        // Act: Execute action and extract response data
        $result = $action->run($request);
        $data = $result->getContent()->toArray();

        // Assert: Verify all expected data fields are present and correct
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
        // Arrange: Create action with null values
        $action = new TestAction;
        $request = new TestApiRecord(
            id: null,
            name: null,
            email: null,
        );

        // Act: Execute action with nulls
        $result = $action->run($request);
        $data = $result->getContent()->toArray();

        // Assert: Verify default values were applied
        $this->assertSame('1', $data['id']);
        $this->assertSame('Test User 1', $data['name']);
        $this->assertSame('test1@example.com', $data['email']);
    }
}
