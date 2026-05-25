<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\Actions;

use AndyDefer\Actions\Tests\IntegrationTestCase;
use AndyDefer\Actions\Tests\Fixtures\Actions\TestAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\TestActionWithHooks;
use AndyDefer\Actions\Tests\Fixtures\Records\TestApiRecord;
use AndyDefer\Records\EmptyRecord;
use Illuminate\Http\JsonResponse;
use Exception;

final class AbstractActionIntegrationTest extends IntegrationTestCase
{
    public function test_action_can_return_json_response(): void
    {
        $action = new TestAction();
        $request = new TestApiRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );

        $response = $action->run($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
    }

    public function test_action_calls_before_hook(): void
    {
        $action = new TestActionWithHooks();
        $request = new TestApiRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );

        $action->run($request);

        $this->assertTrue($action->beforeCalled);
        $this->assertTrue($action->afterCalled);
        $this->assertTrue($action->afterSuccess);
    }

    public function test_action_calls_after_hook_with_error_on_exception(): void
    {
        $action = new TestActionWithHooks();
        $request = new TestApiRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        );

        $action->shouldThrow = true;

        try {
            $action->run($request);
        } catch (Exception $e) {
            // Exception attendue
        }

        $this->assertTrue($action->beforeCalled);
        $this->assertTrue($action->afterCalled);
        $this->assertFalse($action->afterSuccess);
        $this->assertNotNull($action->afterError);
    }

    public function test_action_can_get_request(): void
    {
        $action = new TestAction();
        $request = new TestApiRecord(
            id: 123,
            name: 'John Doe',
            email: 'john@example.com',
        );

        $action->run($request);

        $this->assertSame(123, $action->getRequest()->id);
        $this->assertSame('John Doe', $action->getRequest()->name);
        $this->assertSame('john@example.com', $action->getRequest()->email);
    }

    public function test_action_returns_empty_record_when_not_set(): void
    {
        $action = new TestAction();

        $action->run(new EmptyRecord());

        $this->assertInstanceOf(EmptyRecord::class, $action->getRequest());
    }

    public function test_action_returns_response_with_correct_data_structure(): void
    {
        $action = new TestAction();
        $request = new TestApiRecord(
            id: 5,
            name: 'Jane Doe',
            email: 'jane@example.com',
        );

        $response = $action->run($request);
        $data = $response->getData(true);

        $this->assertSame('5', $data['id']);
        $this->assertSame('Jane Doe', $data['name']);
        $this->assertSame('jane@example.com', $data['email']);
        $this->assertSame('active', $data['status']);
        $this->assertSame('user', $data['role']);
        $this->assertSame(1, $data['grade']);
        $this->assertIsArray($data['tags']);
        $this->assertIsString($data['createdAt']);
    }
}
