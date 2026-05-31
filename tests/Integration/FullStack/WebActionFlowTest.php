<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\FullStack;

use AndyDefer\Actions\Support\ActionRoute;
use AndyDefer\Actions\Tests\Fixtures\Actions\CastWebAction;
use AndyDefer\Actions\Tests\Fixtures\Actions\TestWebAction;
use AndyDefer\Actions\Tests\Fixtures\Requests\CastWebRequest;
use AndyDefer\Actions\Tests\Fixtures\Requests\TestWebRequest;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Http\UploadedFile;

final class WebActionFlowTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_complete_web_action_flow_with_get_request(): void
    {
        // Arrange
        ActionRoute::get('/dashboard', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->get('/dashboard');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('<h1>Dashboard</h1>', false);
    }

    public function test_web_action_receives_url_parameters_correctly(): void
    {
        // Arrange
        ActionRoute::get('/users/{userId}/profile', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->get('/users/123/profile');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('<h1>User 123 Profile</h1>', false);
    }

    public function test_web_action_casts_url_parameters_to_correct_types(): void
    {
        // Arrange
        ActionRoute::get('/cast/{int}/{float}/{boolTrue}/{boolFalse}', CastWebRequest::class, CastWebAction::class);

        // Act
        $response = $this->get('/cast/42/99.99/true/false');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'castInt' => 42,
            'castFloat' => 99.99,
            'castBoolTrue' => true,
            'castBoolFalse' => false,
        ]);
    }

    public function test_web_action_returns_correct_view(): void
    {
        // Arrange
        ActionRoute::get('/about', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->get('/about');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('<h1>About Us</h1>', false);
    }

    public function test_web_action_passes_data_to_view(): void
    {
        // Arrange
        ActionRoute::get('/contact', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->get('/contact');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('<h1>Contact</h1>', false);
        $response->assertSee('contact@example.com', false);
    }

    public function test_web_action_handles_form_submission(): void
    {
        // Arrange
        ActionRoute::post('/submit-form', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->post('/submit-form', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'submittedName' => 'John Doe',
            'submittedEmail' => 'john@example.com',
        ]);
    }

    public function test_web_action_handles_multiple_concurrent_requests(): void
    {
        // Arrange
        ActionRoute::get('/page/{id}', TestWebRequest::class, TestWebAction::class);

        // Act & Assert
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->get("/page/{$i}");
            $response->assertStatus(200);
            $response->assertSee("<h1>Page {$i}</h1>", false);
        }
    }

    public function test_web_action_preserves_query_parameters(): void
    {
        // Arrange
        ActionRoute::get('/search', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->get('/search?q=test&page=2');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'searchQuery' => 'test',
            'currentPage' => 2,
        ]);
    }

    public function test_web_action_handles_session_data(): void
    {
        // Arrange
        ActionRoute::get('/session-test', TestWebRequest::class, TestWebAction::class);

        session()->put('user_id', 123);
        session()->put('user_name', 'John Doe');

        // Act
        $response = $this->get('/session-test');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'userId' => 123,
            'userName' => 'John Doe',
        ]);
    }

    public function test_web_action_handles_cookies(): void
    {
        // Arrange
        ActionRoute::get('/cookie-test', TestWebRequest::class, TestWebAction::class);

        config()->set('session.encrypt', false);

        // Act
        $response = $this->call('GET', '/cookie-test', [], [
            'preference' => 'dark-mode',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'preference' => 'dark-mode',
        ]);

        config()->set('session.encrypt', true);
    }

    public function test_web_action_handles_flash_messages(): void
    {
        // Arrange
        ActionRoute::get('/flash-test', TestWebRequest::class, TestWebAction::class);

        session()->flash('flash_message', 'Operation successful');

        // Act
        $response = $this->get('/flash-test');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'flashMessage' => 'Operation successful',
        ]);
    }

    public function test_web_action_handles_nested_views(): void
    {
        // Arrange
        ActionRoute::get('/admin/users', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->get('/admin/users');

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['users' => []]);
    }

    public function test_web_action_handles_special_characters_in_view_data(): void
    {
        // Arrange
        ActionRoute::get('/special-chars', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->get('/special-chars');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('&lt;script&gt;alert("test")&lt;/script&gt;', false);
    }

    public function test_web_action_handles_file_uploads(): void
    {
        // Arrange
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        ActionRoute::post('/upload', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->call('POST', '/upload', [], [], [
            'file' => new UploadedFile(
                $tempFile,
                'test.txt',
                'text/plain',
                null,
                true
            ),
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Upload successful']);

        unlink($tempFile);
    }

    public function test_web_action_handles_json_response_for_api_endpoints(): void
    {
        // Arrange
        ActionRoute::get('/api/web-data', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->getJson('/api/web-data');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['id', 'name'],
            'message',
        ]);
        $response->assertJson([
            'data' => ['id' => 1, 'name' => 'Test'],
            'message' => 'Success',
        ]);
    }

    public function test_web_action_handles_ajax_requests(): void
    {
        // Arrange
        ActionRoute::get('/ajax-data', TestWebRequest::class, TestWebAction::class);

        // Act
        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get('/ajax-data');

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['data' => 'ajax response']);
    }
}
