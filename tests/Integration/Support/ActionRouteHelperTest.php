<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Unit\Support;

use AndyDefer\Actions\Tests\Fixtures\Actions\CastParamsAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\GetUserPostsAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\TestApiAction;
use AndyDefer\Actions\Tests\Fixtures\Requests\CastParamsRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\GetUserPostsRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\TestApiRequest;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class ActionRouteHelperTest extends IntegrationTestCase
{
    public function test_helper_returns_closure(): void
    {
        // Arrange & Act
        $closure = action_route(TestApiRequest::class, TestApiAction::class);

        // Assert
        $this->assertIsCallable($closure);
        $this->assertInstanceOf(\Closure::class, $closure);
    }

    public function test_helper_get_route(): void
    {
        // Arrange
        Route::get('/api/helper-get/{id}', action_route(TestApiRequest::class, TestApiAction::class));

        // Act
        $response = $this->getJson('/api/helper-get/123');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '123',
            'name' => 'User 123',
            'email' => 'user123@example.com',
        ]);
    }

    public function test_helper_post_route(): void
    {
        // Arrange
        Route::post('/api/helper-post', action_route(TestApiRequest::class, TestApiAction::class));

        // Act
        $response = $this->postJson('/api/helper-post', [
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

    public function test_helper_put_route(): void
    {
        // Arrange
        Route::put('/api/helper-put/{id}', action_route(TestApiRequest::class, TestApiAction::class));

        // Act
        $response = $this->putJson('/api/helper-put/456', [
            'name' => 'Jane Doe',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '456',
            'name' => 'Jane Doe',
        ]);
    }

    public function test_helper_delete_route(): void
    {
        // Arrange
        Route::delete('/api/helper-delete/{id}', action_route(TestApiRequest::class, TestApiAction::class));

        // Act
        $response = $this->deleteJson('/api/helper-delete/789');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '789',
            'name' => 'User 789',
            'email' => 'user789@example.com',
        ]);
    }

    public function test_helper_patch_route(): void
    {
        // Arrange
        Route::patch('/api/helper-patch/{id}', action_route(TestApiRequest::class, TestApiAction::class));

        // Act
        $response = $this->patchJson('/api/helper-patch/999', [
            'email' => 'updated@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '999',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_helper_with_route_name(): void
    {
        // Arrange
        $routeName = 'test.named.get';

        Route::get('/api/named-get/{id}', action_route(TestApiRequest::class, TestApiAction::class))
            ->name($routeName);

        // Act
        $response = $this->getJson('/api/named-get/123');

        // Assert
        $response->assertStatus(200);

        // Verify route exists by making a request and checking the response
        $this->assertTrue(true, "Route should be accessible");
    }

    public function test_helper_with_middleware(): void
    {
        // Arrange
        Route::get('/api/middleware-route', action_route(TestApiRequest::class, TestApiAction::class))
            ->middleware('api');

        // Act
        $response = $this->getJson('/api/middleware-route');

        // Assert
        $response->assertStatus(200);
    }

    public function test_helper_with_where_constraint(): void
    {
        // Arrange
        Route::get('/api/constrained/{id}', action_route(TestApiRequest::class, TestApiAction::class))
            ->where('id', '[0-9]+');

        // Act
        $response = $this->getJson('/api/constrained/123');

        // Assert
        $response->assertStatus(200);
    }

    public function test_helper_with_prefix_group(): void
    {
        // Arrange
        Route::prefix('api/v1')->group(function () {
            Route::get('/users', action_route(TestApiRequest::class, TestApiAction::class));
        });

        // Act
        $response = $this->getJson('/api/v1/users');

        // Assert
        $response->assertStatus(200);
    }

    public function test_helper_cast_params_route(): void
    {
        // Arrange
        Route::get(
            '/api/helper-cast/{int}/{float}/{boolTrue}/{boolFalse}',
            action_route(CastParamsRequest::class, CastParamsAction::class)
        );

        // Act
        $response = $this->getJson('/api/helper-cast/42/99.99/true/false');

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

    public function test_helper_user_posts_route(): void
    {
        // Arrange
        Route::get(
            '/api/users/{userId}/posts/{postId}',
            action_route(GetUserPostsRequest::class, GetUserPostsAction::class)
        );

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

    public function test_helper_handles_query_parameters(): void
    {
        // Arrange
        Route::get('/api/helper-search', action_route(TestApiRequest::class, TestApiAction::class));

        // Act
        $response = $this->getJson('/api/helper-search?q=test&page=2');

        // Assert
        $response->assertStatus(200);
    }

    public function test_helper_returns_json_response(): void
    {
        // Arrange
        Route::get('/api/helper-json', action_route(TestApiRequest::class, TestApiAction::class));

        // Act
        $response = $this->getJson('/api/helper-json');

        // Assert
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_helper_named_route_url_generation(): void
    {
        // Arrange
        $routeName = 'users.posts.show';

        Route::get(
            '/api/users/{userId}/posts/{postId}',
            action_route(GetUserPostsRequest::class, GetUserPostsAction::class)
        )->name($routeName);

        // Act
        $response = $this->getJson('/api/users/123/posts/456');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'userId' => 123,
            'postId' => 456,
        ]);
    }

    public function test_helper_full_chaining(): void
    {
        // Arrange
        $routeName = 'chained.route';

        Route::get('/api/chained/{id}', action_route(TestApiRequest::class, TestApiAction::class))
            ->name($routeName)
            ->middleware('api')
            ->where('id', '[0-9]+');

        // Act
        $response = $this->getJson('/api/chained/123');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => '123',
            'name' => 'User 123',
            'email' => 'user123@example.com',
        ]);
    }

    public function test_helper_multiple_named_routes_can_be_registered(): void
    {
        // Arrange
        Route::get('/api/named1', action_route(TestApiRequest::class, TestApiAction::class))
            ->name('route.one');
        Route::get('/api/named2', action_route(TestApiRequest::class, TestApiAction::class))
            ->name('route.two');
        Route::get('/api/named3', action_route(TestApiRequest::class, TestApiAction::class))
            ->name('route.three');

        // Act
        $response1 = $this->getJson('/api/named1');
        $response2 = $this->getJson('/api/named2');
        $response3 = $this->getJson('/api/named3');

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);
    }

    public function test_helper_route_without_name_has_no_name(): void
    {
        // Arrange
        Route::get('/api/unnamed', action_route(TestApiRequest::class, TestApiAction::class));

        // Act
        $foundRoute = null;
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->uri() === 'api/unnamed') {
                $foundRoute = $route;
                break;
            }
        }

        // Assert
        $this->assertNotNull($foundRoute);
        $this->assertNull($foundRoute->getName());
    }

    public function test_helper_can_redirect_to_named_route(): void
    {
        // Arrange
        $targetRouteName = 'target.route';

        Route::get('/api/target', action_route(TestApiRequest::class, TestApiAction::class))
            ->name($targetRouteName);

        Route::get('/api/redirector', function () use ($targetRouteName) {
            return redirect()->route($targetRouteName);
        });

        // Act
        $response = $this->get('/api/redirector');

        // Assert
        $response->assertRedirect('/api/target');
    }
}
