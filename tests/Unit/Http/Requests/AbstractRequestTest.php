<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Unit\Http\Requests;

use AndyDefer\Actions\Http\Requests\EmptyRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\NestedTestUserRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\TestUserRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use AndyDefer\DomainStructures\Utils\StrictDataObject;
use Illuminate\Support\Facades\Route;

final class AbstractRequestTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_validated_returns_strict_data_object(): void
    {
        // Arrange: Create a route that uses TestUserRequest
        Route::post('/api/test-validated', function (TestUserRequest $request) {
            return response()->json($request->validated()->toArray());
        });

        // Act: Make a request with valid data
        $response = $this->postJson('/api/test-validated', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        // Assert: Verify response and data structure
        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);
    }

    public function test_validated_allows_array_access(): void
    {
        // Arrange: Create a route that returns validated data
        Route::post('/api/test-array-access', function (TestUserRequest $request) {
            $validated = $request->validated();
            return response()->json([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'age' => $validated['age'],
            ]);
        });

        // Act: Make a request with valid data
        $response = $this->postJson('/api/test-array-access', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        // Assert: Verify array access works
        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);
    }

    public function test_validated_allows_object_property_access(): void
    {
        // Arrange: Create a route that returns validated data via object property
        Route::post('/api/test-object-access', function (TestUserRequest $request) {
            $validated = $request->validated();
            return response()->json([
                'name' => $validated->name,
                'email' => $validated->email,
                'age' => $validated->age,
            ]);
        });

        // Act: Make a request with valid data
        $response = $this->postJson('/api/test-object-access', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'age' => 25,
        ]);

        // Assert: Verify object property access works
        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'age' => 25,
        ]);
    }

    public function test_validated_with_key_returns_single_value(): void
    {
        // Arrange: Create a route that returns single validated values
        Route::post('/api/test-single-value', function (TestUserRequest $request) {
            return response()->json([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'default' => $request->validated('nonexistent', 'default-value'),
            ]);
        });

        // Act: Make a request with valid data
        $response = $this->postJson('/api/test-single-value', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Assert: Verify single values are returned correctly
        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'default' => 'default-value',
        ]);
    }

    public function test_get_validated_returns_strict_data_object(): void
    {
        // Arrange: Create a route that uses getValidated() method
        Route::post('/api/test-get-validated', function (TestUserRequest $request) {
            $validated = $request->getValidated();
            return response()->json($validated->toArray());
        });

        // Act: Make a request with valid data
        $response = $this->postJson('/api/test-get-validated', [
            'name' => 'Alice Wonder',
            'email' => 'alice@example.com',
            'age' => 28,
        ]);

        // Assert: Verify getValidated() returns correct data
        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'Alice Wonder',
            'email' => 'alice@example.com',
            'age' => 28,
        ]);
    }

    public function test_validated_returns_only_validated_fields(): void
    {
        // Arrange: Create a route that returns all validated fields
        Route::post('/api/test-only-validated', function (TestUserRequest $request) {
            $validated = $request->validated();
            return response()->json(array_keys($validated->toArray()));
        });

        // Act: Make a request with extra fields not in validation rules
        $response = $this->postJson('/api/test-only-validated', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'extra_field' => 'should be ignored',
            'another_extra' => 'should also be ignored',
        ]);

        // Assert: Verify only validated fields are present
        $response->assertStatus(200);
        $response->assertJson(['name', 'email', 'age']);
        $response->assertJsonMissing(['extra_field', 'another_extra']);
    }

    public function test_validated_preserves_null_values(): void
    {
        // Arrange: Create a route that returns validated data with null values
        Route::post('/api/test-null-values', function (TestUserRequest $request) {
            $validated = $request->validated();
            return response()->json([
                'has_age' => isset($validated['age']),
                'age_is_null' => $validated['age'] === null,
            ]);
        });

        // Act: Make a request with null age value
        $response = $this->postJson('/api/test-null-values', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => null,
        ]);

        // Assert: Verify null values are preserved
        $response->assertStatus(200);
        $response->assertJson([
            'has_age' => true,
            'age_is_null' => true,
        ]);
    }

    public function test_validated_throws_exception_when_validation_fails(): void
    {
        // Arrange: Create a route that uses TestUserRequest
        Route::post('/api/test-validation-fails', function (TestUserRequest $request) {
            return response()->json(['success' => true]);
        });

        // Act: Make a request with invalid data
        $response = $this->postJson('/api/test-validation-fails', [
            'name' => '',
            'email' => 'invalid-email',
            'age' => 200,
        ]);

        // Assert: Verify validation fails with 422 status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'age']);
    }

    public function test_validated_with_partial_data(): void
    {
        // Arrange: Create a route that returns validated data
        Route::post('/api/test-partial-data', function (TestUserRequest $request) {
            $validated = $request->validated();
            return response()->json([
                'name' => $validated->name,
                'email' => $validated->email,
                'has_age' => isset($validated->age),
            ]);
        });

        // Act: Make a request without optional age field
        $response = $this->postJson('/api/test-partial-data', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Assert: Verify optional field is not present
        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'has_age' => false,
        ]);
    }

    public function test_validated_can_be_used_in_get_record_method(): void
    {
        // Arrange: Create a route that returns the Record from the Request
        Route::post('/api/test-get-record', function (TestUserRequest $request) {
            /** @var TestUserRecord  */
            $record = $request->getRecord();
            return response()->json([
                'id' => $record->id,
                'name' => $record->name,
                'email' => $record->email,
            ]);
        });

        // Act: Make a request with id, name, and email
        $response = $this->postJson('/api/test-get-record', [
            'id' => 123,
            'name' => 'Record User',
            'email' => 'record@example.com',
            'age' => 42,
        ]);

        // Assert: Verify Record contains correct data
        $response->assertStatus(200);
        $response->assertJson([
            'id' => 123,
            'name' => 'Record User',
            'email' => 'record@example.com',
        ]);
    }

    public function test_validated_works_with_nested_data_structures(): void
    {
        // Arrange: Create a route that uses NestedTestUserRequest
        Route::post('/api/test-nested', function (NestedTestUserRequest $request) {
            $validated = $request->validated();
            return response()->json([
                'user_name' => $validated['user']['name'],
                'user_email' => $validated['user']['email'],
                'user_age' => $validated['user']['profile']['age'],
            ]);
        });

        // Act: Make a request with nested data
        $response = $this->postJson('/api/test-nested', [
            'user' => [
                'name' => 'Nested User',
                'email' => 'nested@example.com',
                'profile' => [
                    'age' => 35,
                ],
            ],
        ]);

        // Assert: Verify nested data is accessible
        $response->assertStatus(200);
        $response->assertJson([
            'user_name' => 'Nested User',
            'user_email' => 'nested@example.com',
            'user_age' => 35,
        ]);
    }

    public function test_authorize_returns_true_by_default(): void
    {
        // Arrange: Create a route that checks authorization
        Route::post('/api/test-authorize', function (TestUserRequest $request) {
            return response()->json(['authorized' => $request->authorize()]);
        });

        // Act: Make a request
        $response = $this->postJson('/api/test-authorize', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Assert: Verify default authorize returns true
        $response->assertStatus(200);
        $response->assertJson(['authorized' => true]);
    }

    public function test_rules_returns_empty_array_by_default(): void
    {
        // Arrange: Create a route that uses EmptyRequest
        Route::post('/api/test-empty-rules', function (EmptyRequest $request) {
            $validated = $request->validated();
            return response()->json($validated->toArray());
        });

        // Act: Make a request with any data
        $response = $this->postJson('/api/test-empty-rules', [
            'any_field' => 'any value',
            'another_field' => 123,
        ]);

        // Assert: Since EmptyRequest has no rules, validated() returns an empty array
        // This is expected because no validation rules mean no fields are validated
        $response->assertStatus(200);
        $response->assertJson([]);
    }
}
