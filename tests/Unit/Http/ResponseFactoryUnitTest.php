<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Unit\Http;

use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUserData;
use AndyDefer\Actions\Tests\UnitTestCase;

/**
 * Unit tests for ResponseFactory configuration.
 *
 * These tests verify that the factory correctly stores configuration
 * (type, content, status, headers) without generating actual HTTP responses.
 */
final class ResponseFactoryUnitTest extends UnitTestCase
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
    }

    public function test_json_creates_response_factory_with_json_type(): void
    {
        // Act
        $result = ResponseFactory::json($this->testData, 201);

        // Assert
        $this->assertEquals('json', $result->getType()->value);
        $this->assertSame($this->testData, $result->getContent());
        $this->assertSame(201, $result->getStatus());
    }

    public function test_json_defaults_to_200_status_code(): void
    {
        // Act
        $result = ResponseFactory::json($this->testData);

        // Assert
        $this->assertSame(200, $result->getStatus());
    }

    public function test_redirect_creates_response_factory_with_redirect_type(): void
    {
        // Act
        $result = ResponseFactory::redirect('/dashboard', 301);

        // Assert
        $this->assertEquals('redirect', $result->getType()->value);
        $this->assertSame('/dashboard', $result->getContent());
        $this->assertSame(301, $result->getStatus());
    }

    public function test_redirect_defaults_to_302_status_code(): void
    {
        // Act
        $result = ResponseFactory::redirect('/dashboard');

        // Assert
        $this->assertSame(302, $result->getStatus());
    }

    public function test_redirect_route_creates_response_factory_with_redirect_route_type(): void
    {
        // Act
        $result = ResponseFactory::redirectRoute('home', ['id' => 1], 303);

        // Assert
        $this->assertEquals('redirectRoute', $result->getType()->value);
        $this->assertEquals(['route' => 'home', 'parameters' => ['id' => 1]], $result->getContent());
        $this->assertSame(303, $result->getStatus());
    }

    public function test_redirect_back_creates_response_factory_with_redirect_back_type(): void
    {
        // Act
        $result = ResponseFactory::redirectBack(302);

        // Assert
        $this->assertEquals('redirectBack', $result->getType()->value);
        $this->assertSame(302, $result->getContent());
        $this->assertSame(302, $result->getStatus());
    }

    public function test_no_content_creates_response_factory_with_no_content_type(): void
    {
        // Act
        $result = ResponseFactory::noContent();

        // Assert
        $this->assertEquals('noContent', $result->getType()->value);
        $this->assertNull($result->getContent());
        $this->assertSame(204, $result->getStatus());
    }

    public function test_html_creates_response_factory_with_html_type(): void
    {
        // Arrange
        $htmlContent = '<h1>Hello World</h1>';

        // Act
        $result = ResponseFactory::html($htmlContent, 201);

        // Assert
        $this->assertEquals('html', $result->getType()->value);
        $this->assertSame($htmlContent, $result->getContent());
        $this->assertSame(201, $result->getStatus());
    }

    public function test_html_defaults_to_200_status_code(): void
    {
        // Arrange
        $htmlContent = '<h1>Test</h1>';

        // Act
        $result = ResponseFactory::html($htmlContent);

        // Assert
        $this->assertSame(200, $result->getStatus());
    }

    public function test_text_creates_response_factory_with_text_type(): void
    {
        // Arrange
        $textContent = 'Hello World';

        // Act
        $result = ResponseFactory::text($textContent, 202);

        // Assert
        $this->assertEquals('text', $result->getType()->value);
        $this->assertSame($textContent, $result->getContent());
        $this->assertSame(202, $result->getStatus());
    }

    public function test_text_defaults_to_200_status_code(): void
    {
        // Arrange
        $textContent = 'Hello World';

        // Act
        $result = ResponseFactory::text($textContent);

        // Assert
        $this->assertSame(200, $result->getStatus());
    }

    public function test_view_creates_response_factory_with_view_type(): void
    {
        // Act
        $result = ResponseFactory::view('welcome', ['name' => 'John'], 200);

        // Assert
        $this->assertEquals('view', $result->getType()->value);
        $this->assertEquals(['view' => 'welcome', 'data' => ['name' => 'John']], $result->getContent());
        $this->assertSame(200, $result->getStatus());
    }

    public function test_view_defaults_to_200_status_code(): void
    {
        // Act
        $result = ResponseFactory::view('welcome');

        // Assert
        $this->assertSame(200, $result->getStatus());
    }

    public function test_with_headers_adds_headers_to_response_factory(): void
    {
        // Act
        $result = ResponseFactory::json($this->testData)
            ->withHeaders(['X-Custom' => 'value', 'X-Test' => 'test']);

        // Assert
        $this->assertEquals(['X-Custom' => 'value', 'X-Test' => 'test'], $result->getHeaders());
    }

    public function test_with_status_updates_status_code(): void
    {
        // Act
        $result = ResponseFactory::json($this->testData)
            ->withStatus(202);

        // Assert
        $this->assertSame(202, $result->getStatus());
    }

    public function test_chaining_with_headers_and_status_works_together(): void
    {
        // Act
        $result = ResponseFactory::json($this->testData)
            ->withHeaders(['X-Custom' => 'value'])
            ->withStatus(201);

        // Assert
        $this->assertSame(201, $result->getStatus());
        $this->assertEquals(['X-Custom' => 'value'], $result->getHeaders());
    }

    public function test_file_inline_creates_response_factory_with_file_inline_type(): void
    {
        // Act
        $result = ResponseFactory::fileInline('/path/to/file.pdf', 'custom.pdf');

        // Assert
        $this->assertEquals('fileInline', $result->getType()->value);
        $this->assertEquals(['path' => '/path/to/file.pdf', 'name' => 'custom.pdf'], $result->getContent());
    }

    public function test_file_inline_without_custom_name_uses_basename(): void
    {
        // Act
        $result = ResponseFactory::fileInline('/path/to/file.pdf');
        $content = $result->getContent();

        // Assert
        $this->assertNull($content['name']);
    }

    public function test_file_download_creates_response_factory_with_file_download_type(): void
    {
        // Act
        $result = ResponseFactory::fileDownload('/path/to/file.pdf', 'download.pdf');

        // Assert
        $this->assertEquals('fileDownload', $result->getType()->value);
        $this->assertEquals(['path' => '/path/to/file.pdf', 'name' => 'download.pdf'], $result->getContent());
    }

    public function test_stream_creates_response_factory_with_stream_type(): void
    {
        // Arrange
        $callback = function (): void {};

        // Act
        $result = ResponseFactory::stream($callback, 'video/mp4', 206);

        // Assert
        $this->assertEquals('stream', $result->getType()->value);
        $this->assertEquals(['callback' => $callback, 'contentType' => 'video/mp4'], $result->getContent());
        $this->assertSame(206, $result->getStatus());
    }

    public function test_stream_uses_default_content_type(): void
    {
        // Arrange
        $callback = function (): void {};

        // Act
        $result = ResponseFactory::stream($callback);
        $content = $result->getContent();

        // Assert
        $this->assertEquals('application/octet-stream', $content['contentType']);
        $this->assertSame(200, $result->getStatus());
    }

    public function test_sse_creates_response_factory_with_sse_type(): void
    {
        // Arrange
        $callback = function (): void {};

        // Act
        $result = ResponseFactory::sse($callback);

        // Assert
        $this->assertEquals('sse', $result->getType()->value);
        $this->assertSame($callback, $result->getContent());
        $this->assertSame(200, $result->getStatus());
    }

    public function test_inertia_creates_response_factory_with_inertia_type(): void
    {
        // Act
        $result = ResponseFactory::inertia('Dashboard/Index', ['user' => ['name' => 'John']]);

        // Assert
        $this->assertEquals('inertia', $result->getType()->value);
        $this->assertEquals(
            ['component' => 'Dashboard/Index', 'props' => ['user' => ['name' => 'John']]],
            $result->getContent()
        );
    }

    public function test_get_type_returns_correct_type(): void
    {
        // Act
        $jsonResult = ResponseFactory::json($this->testData);
        $redirectResult = ResponseFactory::redirect('/home');
        $noContentResult = ResponseFactory::noContent();

        // Assert
        $this->assertEquals('json', $jsonResult->getType()->value);
        $this->assertEquals('redirect', $redirectResult->getType()->value);
        $this->assertEquals('noContent', $noContentResult->getType()->value);
    }

    public function test_get_content_returns_correct_content(): void
    {
        // Act
        $jsonResult = ResponseFactory::json($this->testData);
        $redirectResult = ResponseFactory::redirect('/home');
        $htmlResult = ResponseFactory::html('<h1>Test</h1>');

        // Assert
        $this->assertSame($this->testData, $jsonResult->getContent());
        $this->assertSame('/home', $redirectResult->getContent());
        $this->assertSame('<h1>Test</h1>', $htmlResult->getContent());
    }

    public function test_get_headers_returns_empty_array_by_default(): void
    {
        // Act
        $result = ResponseFactory::json($this->testData);

        // Assert
        $this->assertEquals([], $result->getHeaders());
    }
}
