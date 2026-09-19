<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\Http;

use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUserData;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use AndyDefer\PhpVo\Enums\HttpStatusCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Integration tests for ResponseFactory HTTP response generation.
 *
 * These tests verify that toResponse() correctly converts factory configurations
 * into actual Laravel/Symfony HTTP response objects.
 */
final class ResponseFactoryIntegrationTest extends IntegrationTestCase
{
    private TestUserData $testData;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Create reusable test data
        $this->testData = TestUserData::from([
            'id' => '1',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'grade' => 3,
            'emailVerifiedAt' => '2024-01-15T10:30:00Z',
            'tags' => ['admin', 'premium'],
            'createdAt' => '2024-01-15T10:30:00Z',
        ]);

        // Create a named route for redirectRoute tests
        Route::get('/home', fn () => 'home')->name('home');
    }

    public function test_json_to_response_returns_json_response(): void
    {
        // Act
        $factory = ResponseFactory::json($this->testData);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($this->testData->toArray(), $response->getData(true));
    }

    public function test_json_to_response_with_custom_status(): void
    {
        // Act
        $factory = ResponseFactory::json($this->testData, 201);
        $response = $factory->toResponse();

        // Assert
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_json_to_response_with_headers(): void
    {
        // Act
        $factory = ResponseFactory::json($this->testData)
            ->withHeaders(['X-Custom' => 'value']);
        $response = $factory->toResponse();

        // Assert
        $this->assertSame('value', $response->headers->get('X-Custom'));
    }

    public function test_redirect_to_response_returns_redirect_response(): void
    {
        // Act
        $factory = ResponseFactory::redirect('/dashboard', 301);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(301, $response->getStatusCode());
        $this->assertStringEndsWith('/dashboard', $response->getTargetUrl());
    }

    public function test_redirect_route_to_response_returns_redirect_response(): void
    {
        // Act
        $factory = ResponseFactory::redirectRoute('home', [], 303);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertStringEndsWith('/home', $response->getTargetUrl());
    }

    public function test_redirect_back_to_response_returns_redirect_response(): void
    {
        // Act
        $factory = ResponseFactory::redirectBack(302);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_no_content_to_response_returns_204_response(): void
    {
        // Act
        $factory = ResponseFactory::noContent();
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function test_html_to_response_returns_html_response(): void
    {
        // Arrange
        $htmlContent = '<h1>Hello World</h1>';

        // Act
        $factory = ResponseFactory::html($htmlContent, 201);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('text/html', $response->headers->get('Content-Type'));
        $this->assertSame($htmlContent, $response->getContent());
    }

    public function test_text_to_response_returns_text_response(): void
    {
        // Arrange
        $textContent = 'Hello World';

        // Act
        $factory = ResponseFactory::text($textContent, 202);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame($textContent, $response->getContent());
    }

    public function test_view_to_response_returns_view_response(): void
    {
        // Act
        $factory = ResponseFactory::view('welcome', ['name' => 'John']);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_view_to_response_with_custom_status(): void
    {
        // Act
        $factory = ResponseFactory::view('welcome', [], 201);
        $response = $factory->toResponse();

        // Assert
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_stream_to_response_returns_streamed_response(): void
    {
        // Arrange
        $callback = function (): void {
            echo 'test data';
        };

        // Act
        $factory = ResponseFactory::stream($callback, 'text/plain', 206);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
    }

    public function test_sse_to_response_returns_streamed_response_with_sse_headers(): void
    {
        // Arrange
        $callback = function (): void {
            echo "data: test\n\n";
        };

        // Act
        $factory = ResponseFactory::sse($callback);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertSame('keep-alive', $response->headers->get('Connection'));
    }

    public function test_inertia_to_response_returns_inertia_response(): void
    {
        // Act
        $factory = ResponseFactory::inertia('Dashboard/Index', ['user' => ['name' => 'John']]);
        $response = $factory->toResponse();

        // Assert
        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_to_response_preserves_headers(): void
    {
        // Act
        $factory = ResponseFactory::json($this->testData)
            ->withHeaders(['X-Test' => 'value']);
        $response = $factory->toResponse();

        // Assert
        $this->assertSame('value', $response->headers->get('X-Test'));
    }

    // =========================================================================
    // HttpStatusCode enum support
    // =========================================================================

    public function test_json_accepts_http_status_code_enum(): void
    {
        $response = ResponseFactory::json($this->testData, HttpStatusCode::CREATED)->toResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_redirect_accepts_http_status_code_enum(): void
    {
        $response = ResponseFactory::redirect('/dashboard', HttpStatusCode::MOVED_PERMANENTLY)->toResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(301, $response->getStatusCode());
    }

    public function test_redirect_route_accepts_http_status_code_enum(): void
    {
        $response = ResponseFactory::redirectRoute('home', [], HttpStatusCode::SEE_OTHER)->toResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(303, $response->getStatusCode());
    }

    public function test_redirect_back_accepts_http_status_code_enum(): void
    {
        $response = ResponseFactory::redirectBack(HttpStatusCode::FOUND)->toResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_html_accepts_http_status_code_enum(): void
    {
        $response = ResponseFactory::html('<h1>Hi</h1>', HttpStatusCode::CREATED)->toResponse();

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_text_accepts_http_status_code_enum(): void
    {
        $response = ResponseFactory::text('hello', HttpStatusCode::ACCEPTED)->toResponse();

        $this->assertSame(202, $response->getStatusCode());
    }

    public function test_view_accepts_http_status_code_enum(): void
    {
        $response = ResponseFactory::view('welcome', [], HttpStatusCode::CREATED)->toResponse();

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_stream_accepts_http_status_code_enum(): void
    {
        $callback = function (): void {
            echo 'data';
        };

        $response = ResponseFactory::stream($callback, 'text/plain', HttpStatusCode::PARTIAL_CONTENT)->toResponse();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(206, $response->getStatusCode());
    }

    public function test_with_status_accepts_http_status_code_enum(): void
    {
        $response = ResponseFactory::json($this->testData)
            ->withStatus(HttpStatusCode::ACCEPTED)
            ->toResponse();

        $this->assertSame(202, $response->getStatusCode());
    }

    // =========================================================================
    // Invalid status code validation
    // =========================================================================

    public function test_json_rejects_invalid_int_status_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseFactory::json($this->testData, 9999);
    }

    public function test_redirect_rejects_invalid_int_status_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseFactory::redirect('/dashboard', 9999);
    }

    public function test_redirect_route_rejects_invalid_int_status_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseFactory::redirectRoute('home', [], 9999);
    }

    public function test_redirect_back_rejects_invalid_int_status_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseFactory::redirectBack(9999);
    }

    public function test_html_rejects_invalid_int_status_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseFactory::html('<h1>Hi</h1>', 9999);
    }

    public function test_text_rejects_invalid_int_status_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseFactory::text('hello', 9999);
    }

    public function test_view_rejects_invalid_int_status_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseFactory::view('welcome', [], 9999);
    }

    public function test_stream_rejects_invalid_int_status_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseFactory::stream(fn () => null, 'text/plain', 9999);
    }

    public function test_with_status_rejects_invalid_int_status_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResponseFactory::json($this->testData)->withStatus(9999);
    }

    public function test_json_accepts_valid_int_status_code(): void
    {
        $response = ResponseFactory::json($this->testData, 201)->toResponse();

        $this->assertSame(201, $response->getStatusCode());
    }
}
