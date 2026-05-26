<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\FullStack;

use AndyDefer\Actions\Tests\Fixtures\Actions\TestApiAction;
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
        Route::get('/api/users/{id}', function (TestApiRequest $request, TestApiAction $action, $id) {
            return $action->run($request->toRecord(
                id: (int) $id,
                name: 'User '.$id,
                email: 'user'.$id.'@example.com'
            ));
        });

        $response = $this->getJson('/api/users/123');

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
        Route::post('/api/users', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord(
                name: 'John Doe',
                email: 'john@example.com'
            ));
        });

        $response = $this->postJson('/api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_api_action_receives_url_parameters_correctly(): void
    {
        Route::get('/api/users/{userId}/posts/{postId}', function (TestApiRequest $request, TestApiAction $action, $userId, $postId) {
            return $action->run($request->toRecord(
                id: (int) $userId,
                postId: (int) $postId
            ));
        });

        $response = $this->getJson('/api/users/456/posts/789');

        $response->assertStatus(200);
        $response->assertJson([
            'id' => '456',
        ]);
    }

    public function test_api_action_casts_url_parameters_to_correct_types(): void
    {
        Route::get('/api/cast/{int}/{float}/{boolTrue}/{boolFalse}', function (TestApiRequest $request, TestApiAction $action, $int, $float, $boolTrue, $boolFalse) {
            return $action->run($request->toRecord(
                id: (int) $int,
                float: (float) $float,
                boolTrue: $boolTrue === 'true',
                boolFalse: $boolFalse === 'false'
            ));
        });

        $response = $this->getJson('/api/cast/42/99.99/true/false');

        $response->assertStatus(200);
    }

    public function test_api_action_handles_optional_parameters(): void
    {
        Route::get('/api/users/{id?}', function (TestApiRequest $request, TestApiAction $action, $id = null) {
            return $action->run($request->toRecord(id: $id ? (int) $id : null));
        });

        $response = $this->getJson('/api/users');
        $response->assertStatus(200);
        $response->assertJson(['id' => '1']);

        $response = $this->getJson('/api/users/789');
        $response->assertStatus(200);
        $response->assertJson(['id' => '789']);
    }

    public function test_api_action_returns_correct_response_structure(): void
    {
        Route::get('/api/me', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->getJson('/api/me');

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
        Route::get('/api/user-with-data', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->getJson('/api/user-with-data');
        $data = $response->json();

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
        Route::get('/api/users/{id}', function (TestApiRequest $request, TestApiAction $action, $id) {
            return $action->run($request->toRecord(id: (int) $id));
        });

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->getJson("/api/users/{$i}");
            $response->assertStatus(200);
            $response->assertJson(['id' => (string) $i]);
        }
    }

    public function test_api_action_preserves_query_parameters(): void
    {
        Route::get('/api/search', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->getJson('/api/search?q=test&page=2&limit=10');

        $response->assertStatus(200);
    }

    public function test_api_action_works_with_custom_middleware(): void
    {
        Route::get('/api/protected', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord());
        })->middleware('api');

        $response = $this->getJson('/api/protected');
        $response->assertStatus(200);
    }

    public function test_api_action_handles_request_validation_errors(): void
    {
        Route::post('/api/validate', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->postJson('/api/validate', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
    }

    public function test_api_action_returns_consistent_response_format(): void
    {
        Route::get('/api/consistent', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord());
        });

        $response1 = $this->getJson('/api/consistent');
        $response2 = $this->getJson('/api/consistent');

        $this->assertSame($response1->json(), $response2->json());
    }

    public function test_api_action_with_different_http_methods(): void
    {
        Route::get('/api/resource', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord());
        });
        $response = $this->getJson('/api/resource');
        $response->assertStatus(200);

        Route::post('/api/resource', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord());
        });
        $response = $this->postJson('/api/resource');
        $response->assertStatus(200);

        Route::put('/api/resource/{id}', function (TestApiRequest $request, TestApiAction $action, $id) {
            return $action->run($request->toRecord(id: (int) $id));
        });
        $response = $this->putJson('/api/resource/1');
        $response->assertStatus(200);

        Route::delete('/api/resource/{id}', function (TestApiRequest $request, TestApiAction $action, $id) {
            return $action->run($request->toRecord(id: (int) $id));
        });
        $response = $this->deleteJson('/api/resource/1');
        $response->assertStatus(200);
    }

    public function test_api_action_handles_special_characters_in_parameters(): void
    {
        Route::get('/api/encode/{value}', function (TestApiRequest $request, TestApiAction $action, $value) {
            return $action->run($request->toRecord(value: $value));
        });

        $response = $this->getJson('/api/encode/hello%20world%21');
        $response->assertStatus(200);
    }

    public function test_api_action_works_with_route_names(): void
    {
        Route::get('/api/named', function (TestApiRequest $request, TestApiAction $action) {
            return $action->run($request->toRecord());
        })->name('api.named');

        $response = $this->getJson(route('api.named'));
        $response->assertStatus(200);
    }
}
