<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Unit\Support;

use AndyDefer\Actions\Support\ActionRoute;
use AndyDefer\Actions\Tests\Fixtures\Actions\CastParamsAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\GetUserPostsAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\TestApiAction;
use AndyDefer\Actions\Tests\Fixtures\Requests\CastParamsRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\GetUserPostsRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\TestApiRequest;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

final class ActionRouteTest extends IntegrationTestCase
{
    // ==================== BASIC ROUTE TESTS ====================

    public function test_register_get_route_with_action(): void
    {
        // Arrange
        ActionRoute::get('/api/test-get/{id}', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->getJson('/api/test-get/123');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '123',
            'name' => 'User 123',
            'email' => 'user123@example.com',
        ]);
    }

    public function test_register_post_route_with_action(): void
    {
        // Arrange
        ActionRoute::post('/api/test-post', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->postJson('/api/test-post', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_register_put_route_with_action(): void
    {
        // Arrange
        ActionRoute::put('/api/test-put/{id}', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->putJson('/api/test-put/456', [
            'name' => 'Jane Doe',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '456',
            'name' => 'Jane Doe',
        ]);
    }

    public function test_register_delete_route_with_action(): void
    {
        // Arrange
        ActionRoute::delete('/api/test-delete/{id}', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->deleteJson('/api/test-delete/789');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '789',
            'name' => 'User 789',
            'email' => 'user789@example.com',
        ]);
    }

    public function test_register_patch_route_with_action(): void
    {
        // Arrange
        ActionRoute::patch('/api/test-patch/{id}', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->patchJson('/api/test-patch/999', [
            'email' => 'updated@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '999',
            'email' => 'updated@example.com',
        ]);
    }

    // ==================== CAST PARAMS TESTS ====================

    public function test_cast_params_route_converts_int_float_and_bool(): void
    {
        // Arrange
        ActionRoute::get('/api/cast/{int}/{float}/{boolTrue}/{boolFalse}', CastParamsRequest::class, CastParamsAction::class);

        // Act
        $response = $this->getJson('/api/cast/42/99.99/true/false');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'int' => 42,
            'float' => 99.99,
            'boolTrue' => true,
            'boolFalse' => false,
            'id' => '42',
            'name' => 'User 42',
            'email' => 'user42@example.com',
        ]);
    }

    // ==================== USER POSTS ROUTE TESTS ====================

    public function test_user_posts_route_with_multiple_parameters(): void
    {
        // Arrange
        ActionRoute::get('/api/users/{userId}/posts/{postId}', GetUserPostsRequest::class, GetUserPostsAction::class);

        // Act
        $response = $this->getJson('/api/users/456/posts/789');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'userId' => 456,
            'postId' => 789,
            'message' => 'User 456 posts, showing post 789',
        ]);
    }

    // ==================== ROUTE GROUP TESTS ====================

    public function test_route_with_prefix(): void
    {
        // Arrange
        Route::prefix('api/v1')->group(function () {
            ActionRoute::get('/users', TestApiRequest::class, TestApiAction::class);
        });

        // Act
        $response = $this->getJson('/api/v1/users');

        // Assert
        $response->assertStatus(200);
    }

    public function test_route_with_middleware(): void
    {
        // Arrange
        Route::middleware('api')->group(function () {
            ActionRoute::get('/api/middleware-test', TestApiRequest::class, TestApiAction::class);
        });

        // Act
        $response = $this->getJson('/api/middleware-test');

        // Assert
        $response->assertStatus(200);
    }

    // ==================== QUERY PARAMETER TESTS ====================

    public function test_route_handles_query_parameters(): void
    {
        // Arrange
        ActionRoute::get('/api/search', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->getJson('/api/search?q=test&page=2');

        // Assert
        $response->assertStatus(200);
    }

    // ==================== RESPONSE TYPE TESTS ====================

    public function test_action_returns_json_response(): void
    {
        // Arrange
        ActionRoute::get('/api/json-response', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->getJson('/api/json-response');

        // Assert
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    // ==================== MULTIPLE ROUTES TESTS ====================

    public function test_multiple_routes_can_be_registered(): void
    {
        // Arrange
        ActionRoute::get('/api/route1', TestApiRequest::class, TestApiAction::class);
        ActionRoute::get('/api/route2', TestApiRequest::class, TestApiAction::class);
        ActionRoute::get('/api/route3', TestApiRequest::class, TestApiAction::class);

        // Act
        $response1 = $this->getJson('/api/route1');
        $response2 = $this->getJson('/api/route2');
        $response3 = $this->getJson('/api/route3');

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_throws_exception_when_request_class_does_not_exist(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Request class "InvalidClass" does not exist');

        // Act
        ActionRoute::get('/api/invalid-request', 'InvalidClass', TestApiAction::class);
    }

    public function test_throws_exception_when_action_class_does_not_exist(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Action class "InvalidClass" does not exist');

        // Act
        ActionRoute::get('/api/invalid-action', TestApiRequest::class, 'InvalidClass');
    }

    public function test_throws_exception_when_request_class_does_not_extend_abstract_request(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend');

        // Act
        ActionRoute::get('/api/invalid-request', \stdClass::class, TestApiAction::class);
    }

    public function test_throws_exception_when_action_class_does_not_extend_abstract_action(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend');

        // Act
        ActionRoute::get('/api/invalid-action', TestApiRequest::class, \stdClass::class);
    }
}
