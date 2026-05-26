<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\Traits\Http;

use AndyDefer\Actions\Tests\Fixtures\Actions\TestAction;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUserData;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SendsHttpResponsesTest extends IntegrationTestCase
{
    private TestAction $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new TestAction;

        // Créer une route nommée pour les tests de redirectRoute
        Route::get('/home', function () {
            return 'home';
        })->name('home');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_json_returns_json_response_with_data(): void
    {
        $testData = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            emailVerifiedAt: '2024-01-15T10:30:00Z',
            tags: ['admin', 'premium'],
            createdAt: '2024-01-15T10:30:00Z',
        );

        $response = $this->sut->json($testData, 201);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($testData->toArray(), $response->getData(true));
    }

    public function test_json_defaults_to_200_status_code(): void
    {
        $testData = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: [],
            createdAt: '2024-01-15T10:30:00Z',
        );

        $response = $this->sut->json($testData);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_no_content_returns_204_response(): void
    {
        $response = $this->sut->noContent();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function test_redirect_returns_redirect_response(): void
    {
        $response = $this->sut->redirect('/dashboard', 301);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(301, $response->getStatusCode());
        $this->assertStringEndsWith('/dashboard', $response->getTargetUrl());
    }

    public function test_redirect_defaults_to_302_status_code(): void
    {
        $response = $this->sut->redirect('/dashboard');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_redirect_route_returns_redirect_response(): void
    {
        $response = $this->sut->redirectRoute('home', [], 303);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(303, $response->getStatusCode());
    }

    public function test_redirect_back_returns_redirect_response(): void
    {
        $response = $this->sut->redirectBack(302);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_stream_returns_streamed_response(): void
    {
        $callback = function (): void {
            echo 'test data';
        };

        $response = $this->sut->stream($callback, 'video/mp4', 206);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('video/mp4', $response->headers->get('Content-Type'));
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));
    }

    public function test_stream_uses_default_content_type_when_none_provided(): void
    {
        $callback = function (): void {};

        $response = $this->sut->stream($callback);

        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
    }

    public function test_sse_returns_properly_configured_server_sent_events_response(): void
    {
        $callback = function (): void {
            echo "data: test\n\n";
        };

        $response = $this->sut->sse($callback);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertSame('keep-alive', $response->headers->get('Connection'));
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));
    }

    public function test_html_returns_html_response(): void
    {
        $htmlContent = '<h1>Hello World</h1>';
        $statusCode = 201;

        $response = $this->sut->html($htmlContent, $statusCode);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame('text/html', $response->headers->get('Content-Type'));
        $this->assertSame($htmlContent, $response->getContent());
    }

    public function test_html_defaults_to_200_status_code(): void
    {
        $htmlContent = '<h1>Test</h1>';

        $response = $this->sut->html($htmlContent);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_text_returns_plain_text_response(): void
    {
        $textContent = 'Hello World';
        $statusCode = 202;

        $response = $this->sut->text($textContent, $statusCode);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame($textContent, $response->getContent());
    }

    public function test_text_defaults_to_200_status_code(): void
    {
        $textContent = 'Hello World';

        $response = $this->sut->text($textContent);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_file_inline_returns_file_with_inline_disposition(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');
        $customFileName = 'custom.pdf';

        $response = $this->sut->fileInline($tempFile, $customFileName);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'inline; filename="'.$customFileName.'"',
            $response->headers->get('Content-Disposition')
        );

        unlink($tempFile);
    }

    public function test_file_inline_uses_original_filename_when_no_custom_name_provided(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        $expectedFileName = basename($tempFile);

        $response = $this->sut->fileInline($tempFile);

        $this->assertStringContainsString(
            'inline; filename="'.$expectedFileName.'"',
            $response->headers->get('Content-Disposition')
        );

        unlink($tempFile);
    }

    public function test_file_download_returns_file_with_attachment_disposition(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');
        $customFileName = 'download.pdf';

        $response = $this->sut->fileDownload($tempFile, $customFileName);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            $customFileName,
            $response->headers->get('Content-Disposition')
        );

        unlink($tempFile);
    }

    public function test_file_download_uses_original_filename_when_no_custom_name_provided(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        $expectedFileName = basename($tempFile);

        $response = $this->sut->fileDownload($tempFile);

        $this->assertStringContainsString(
            $expectedFileName,
            $response->headers->get('Content-Disposition')
        );

        unlink($tempFile);
    }

    public function test_view_returns_view_response(): void
    {
        $response = $this->sut->view('welcome', ['name' => 'John'], 200);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_view_defaults_to_200_status_code(): void
    {
        $response = $this->sut->view('welcome');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_inertia_returns_inertia_response(): void
    {
        $componentName = 'Dashboard/Index';

        $response = $this->sut->inertia($componentName, ['user' => ['name' => 'John']]);

        $this->assertInstanceOf(\Inertia\Response::class, $response);
    }
}
