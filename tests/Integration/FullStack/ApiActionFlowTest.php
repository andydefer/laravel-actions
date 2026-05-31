<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\FullStack;

use AndyDefer\Actions\Support\ActionRoute;
use AndyDefer\Actions\Tests\Fixtures\Actions\CastParamsAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\EncodeAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\GetUserPostsAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\TestApiAction;
use AndyDefer\Actions\Tests\Fixtures\Requests\CastParamsRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\EncodeRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\GetUserPostsRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\TestApiRequest;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class ApiActionFlowTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_complete_api_action_flow_with_get_request(): void
    {
        // Arrange
        ActionRoute::get('/api/users/{id}', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->getJson('/api/users/123');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
            'status',
            'role',
            'grade',
            'emailVerifiedAt',
            'tags',
            'createdAt',
        ]);
        $response->assertJson([
            'id' => '123',
            'name' => 'User 123',
            'email' => 'user123@example.com',
        ]);
    }

    public function test_complete_api_action_flow_with_post_request(): void
    {
        // Arrange
        ActionRoute::post('/api/users', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->postJson('/api/users', [
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

    public function test_api_action_receives_url_parameters_correctly(): void
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

    public function test_api_action_casts_url_parameters_to_correct_types(): void
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

    public function test_api_action_handles_optional_parameters(): void
    {
        // Arrange
        ActionRoute::get('/api/users/{id?}', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->getJson('/api/users');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '1',
            'name' => 'User 1',
            'email' => 'user1@example.com',
        ]);

        // Act
        $response = $this->getJson('/api/users/789');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '789',
            'name' => 'User 789',
            'email' => 'user789@example.com',
        ]);
    }

    public function test_api_action_returns_correct_response_structure(): void
    {
        // Arrange
        ActionRoute::get('/api/me', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->getJson('/api/me');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
            'status',
            'role',
            'grade',
            'emailVerifiedAt',
            'tags',
            'createdAt',
        ]);
    }

    public function test_api_action_response_contains_valid_data_types(): void
    {
        // Arrange
        ActionRoute::get('/api/user-with-data', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->getJson('/api/user-with-data');
        $data = $response->json();

        // Assert
        $this->assertIsString($data['id']);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['email']);
        $this->assertIsString($data['status']);
        $this->assertIsString($data['role']);
        $this->assertIsInt($data['grade']);
        $this->assertIsArray($data['tags']);
        $this->assertIsString($data['createdAt']);
    }

    public function test_api_action_handles_multiple_concurrent_requests(): void
    {
        // Arrange
        ActionRoute::get('/api/users/{id}', TestApiRequest::class, TestApiAction::class);

        // Act & Assert
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->getJson("/api/users/{$i}");
            $response->assertStatus(200);
            $response->assertJson([
                'id' => (string) $i,
                'name' => 'User '.$i,
                'email' => 'user'.$i.'@example.com',
            ]);
        }
    }

    public function test_api_action_preserves_query_parameters(): void
    {
        // Arrange
        ActionRoute::get('/api/search', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->getJson('/api/search?q=test&page=2&limit=10');

        // Assert
        $response->assertStatus(200);
    }

    public function test_api_action_works_with_custom_middleware(): void
    {
        // Arrange
        Route::middleware('api')->group(function () {
            ActionRoute::get('/api/protected', TestApiRequest::class, TestApiAction::class);
        });

        // Act
        $response = $this->getJson('/api/protected');

        // Assert
        $response->assertStatus(200);
    }

    public function test_api_action_handles_request_validation_errors(): void
    {
        // Arrange
        ActionRoute::post('/api/validate', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->postJson('/api/validate', [
            'email' => 'invalid-email',
        ]);

        // Assert
        $response->assertStatus(422);
    }

    public function test_api_action_returns_consistent_response_format(): void
    {
        // Arrange
        ActionRoute::get('/api/consistent', TestApiRequest::class, TestApiAction::class);

        // Act
        $response1 = $this->getJson('/api/consistent');
        $response2 = $this->getJson('/api/consistent');

        // Assert
        $this->assertSame($response1->json(), $response2->json());
    }

    public function test_api_action_with_different_http_methods(): void
    {
        // Arrange
        ActionRoute::get('/api/resource', TestApiRequest::class, TestApiAction::class);
        ActionRoute::post('/api/resource', TestApiRequest::class, TestApiAction::class);
        ActionRoute::put('/api/resource/{id}', TestApiRequest::class, TestApiAction::class);
        ActionRoute::delete('/api/resource/{id}', TestApiRequest::class, TestApiAction::class);

        // Act & Assert
        $getResponse = $this->getJson('/api/resource');
        $getResponse->assertStatus(200);
        $getResponse->assertJson([
            'name' => 'User 1',
            'email' => 'user1@example.com',
        ]);

        $postResponse = $this->postJson('/api/resource');
        $postResponse->assertStatus(200);
        $postResponse->assertJson([
            'name' => 'User 1',
            'email' => 'user1@example.com',
        ]);

        $putResponse = $this->putJson('/api/resource/1');
        $putResponse->assertStatus(200);
        $putResponse->assertJson([
            'id' => '1',
            'name' => 'User 1',
            'email' => 'user1@example.com',
        ]);

        $deleteResponse = $this->deleteJson('/api/resource/1');
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson([
            'id' => '1',
            'name' => 'User 1',
            'email' => 'user1@example.com',
        ]);
    }

    public function test_api_action_handles_special_characters_in_parameters(): void
    {
        // Arrange
        ActionRoute::get('/api/encode/{value}', EncodeRequest::class, EncodeAction::class);

        // Act
        $response = $this->getJson('/api/encode/hello%20world%21');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'value' => 'hello world!',
        ]);
    }

    public function test_api_action_works_with_route_names(): void
    {
        // Arrange
        ActionRoute::get('/api/named', TestApiRequest::class, TestApiAction::class);

        // Act
        $response = $this->getJson('/api/named');

        // Assert
        $response->assertStatus(200);
    }
}
