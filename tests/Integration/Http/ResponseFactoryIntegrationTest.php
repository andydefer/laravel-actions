<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\Http;

use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUserData;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ResponseFactoryIntegrationTest extends IntegrationTestCase
{
    private TestUserData $testData;

    protected function setUp(): void
    {
        parent::setUp();

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

        Route::get('/home', fn() => 'home')->name('home');
    }

    public function test_json_to_response_returns_json_response(): void
    {
        $factory = ResponseFactory::json($this->testData);
        $response = $factory->toResponse();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($this->testData->toArray(), $response->getData(true));
    }

    public function test_json_to_response_with_custom_status(): void
    {
        $factory = ResponseFactory::json($this->testData, 201);
        $response = $factory->toResponse();

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_json_to_response_with_headers(): void
    {
        $factory = ResponseFactory::json($this->testData)
            ->withHeaders(['X-Custom' => 'value']);
        $response = $factory->toResponse();

        $this->assertSame('value', $response->headers->get('X-Custom'));
    }

    public function test_redirect_to_response_returns_redirect_response(): void
    {
        $factory = ResponseFactory::redirect('/dashboard', 301);
        $response = $factory->toResponse();

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertSame(301, $response->getStatusCode());
        $this->assertStringEndsWith('/dashboard', $response->getTargetUrl());
    }

    public function test_redirect_route_to_response_returns_redirect_response(): void
    {
        $factory = ResponseFactory::redirectRoute('home', [], 303);
        $response = $factory->toResponse();

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertStringEndsWith('/home', $response->getTargetUrl());
    }

    public function test_redirect_back_to_response_returns_redirect_response(): void
    {
        $factory = ResponseFactory::redirectBack(302);
        $response = $factory->toResponse();

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_no_content_to_response_returns_204_response(): void
    {
        $factory = ResponseFactory::noContent();
        $response = $factory->toResponse();

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function test_html_to_response_returns_html_response(): void
    {
        $htmlContent = '<h1>Hello World</h1>';
        $factory = ResponseFactory::html($htmlContent, 201);
        $response = $factory->toResponse();

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('text/html', $response->headers->get('Content-Type'));
        $this->assertSame($htmlContent, $response->getContent());
    }

    public function test_text_to_response_returns_text_response(): void
    {
        $textContent = 'Hello World';
        $factory = ResponseFactory::text($textContent, 202);
        $response = $factory->toResponse();

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame($textContent, $response->getContent());
    }

    public function test_view_to_response_returns_view_response(): void
    {
        $factory = ResponseFactory::view('welcome', ['name' => 'John']);
        $response = $factory->toResponse();

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_view_to_response_with_custom_status(): void
    {
        $factory = ResponseFactory::view('welcome', [], 201);
        $response = $factory->toResponse();

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_stream_to_response_returns_streamed_response(): void
    {
        $callback = function (): void {
            echo 'test data';
        };

        $factory = ResponseFactory::stream($callback, 'text/plain', 206);
        $response = $factory->toResponse();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
    }

    public function test_sse_to_response_returns_streamed_response_with_sse_headers(): void
    {
        $callback = function (): void {
            echo "data: test\n\n";
        };

        $factory = ResponseFactory::sse($callback);
        $response = $factory->toResponse();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertSame('keep-alive', $response->headers->get('Connection'));
    }

    public function test_inertia_to_response_returns_inertia_response(): void
    {
        $factory = ResponseFactory::inertia('Dashboard/Index', ['user' => ['name' => 'John']]);
        $response = $factory->toResponse();

        $this->assertInstanceOf(\Inertia\Response::class, $response);
    }

    public function test_to_response_preserves_headers(): void
    {
        $factory = ResponseFactory::json($this->testData)
            ->withHeaders(['X-Test' => 'value']);
        $response = $factory->toResponse();

        $this->assertSame('value', $response->headers->get('X-Test'));
    }
}
