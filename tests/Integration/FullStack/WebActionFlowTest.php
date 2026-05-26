<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\FullStack;

use AndyDefer\Actions\Tests\Fixtures\Actions\TestWebAction;
use AndyDefer\Actions\Tests\Fixtures\Requests\TestWebRequest;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;

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
        Route::get('/dashboard', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('<h1>Dashboard</h1>', false);
    }

    public function test_web_action_receives_url_parameters_correctly(): void
    {
        Route::get('/users/{userId}/profile', function (TestWebRequest $request, TestWebAction $action, $userId) {
            return $action->run($request->toRecord(userId: (int) $userId));
        });

        $response = $this->get('/users/123/profile');

        $response->assertStatus(200);
        $response->assertSee('<h1>User 123 Profile</h1>', false);
    }

    public function test_web_action_casts_url_parameters_to_correct_types(): void
    {
        Route::get('/cast/{int}/{float}/{boolTrue}/{boolFalse}', function (TestWebRequest $request, TestWebAction $action, $int, $float, $boolTrue, $boolFalse) {

            $record = $request->toRecord(
                castInt: (int) $int,
                castFloat: (float) $float,
                castBoolTrue: $boolTrue,   // string 'true'
                castBoolFalse: $boolFalse  // string 'false'
            );

            return $action->run($record);
        });

        $response = $this->get('/cast/42/99.99/true/false');

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
        Route::get('/about', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('<h1>About Us</h1>', false);
    }

    public function test_web_action_passes_data_to_view(): void
    {
        Route::get('/contact', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('<h1>Contact</h1>', false);
        $response->assertSee('contact@example.com', false);
    }

    public function test_web_action_handles_form_submission(): void
    {
        Route::post('/submit-form', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->post('/submit-form', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'submittedName' => 'John Doe',
            'submittedEmail' => 'john@example.com',
        ]);
    }

    public function test_web_action_handles_multiple_concurrent_requests(): void
    {
        Route::get('/page/{id}', function (TestWebRequest $request, TestWebAction $action, $id) {
            return $action->run($request->toRecord(id: (int) $id));
        });

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->get("/page/{$i}");
            $response->assertStatus(200);
            $response->assertSee("<h1>Page {$i}</h1>", false);
        }
    }

    public function test_web_action_preserves_query_parameters(): void
    {
        Route::get('/search', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->get('/search?q=test&page=2');

        $response->assertStatus(200);
        $response->assertJson([
            'searchQuery' => 'test',
            'currentPage' => 2,
        ]);
    }

    public function test_web_action_handles_session_data(): void
    {
        Route::get('/session-test', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        session()->put('user_id', 123);
        session()->put('user_name', 'John Doe');

        $response = $this->get('/session-test');

        $response->assertStatus(200);
        $response->assertJson([
            'userId' => 123,
            'userName' => 'John Doe',
        ]);
    }

    public function test_web_action_handles_cookies(): void
    {
        Route::get('/cookie-test', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        // Désactiver complètement l'encryption des cookies
        config()->set('session.encrypt', false);

        // Créer un cookie non encrypté manuellement
        $response = $this->call('GET', '/cookie-test', [], [
            'preference' => 'dark-mode',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'preference' => 'dark-mode',
        ]);

        config()->set('session.encrypt', true);
    }

    public function test_web_action_handles_flash_messages(): void
    {
        Route::get('/flash-test', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        session()->flash('flash_message', 'Operation successful');

        $response = $this->get('/flash-test');

        $response->assertStatus(200);
        $response->assertJson([
            'flashMessage' => 'Operation successful',
        ]);
    }

    public function test_web_action_handles_nested_views(): void
    {
        Route::get('/admin/users', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->get('/admin/users');

        $response->assertStatus(200);
        $response->assertJson(['users' => []]);
    }

    public function test_web_action_handles_special_characters_in_view_data(): void
    {
        Route::get('/special-chars', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->get('/special-chars');

        $response->assertStatus(200);
        $response->assertSee('&lt;script&gt;alert("test")&lt;/script&gt;', false);
    }

    public function test_web_action_handles_file_uploads(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        Route::post('/upload', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->call('POST', '/upload', [], [], [
            'file' => new UploadedFile(
                $tempFile,
                'test.txt',
                'text/plain',
                null,
                true
            ),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Upload successful']);

        unlink($tempFile);
    }

    public function test_web_action_handles_json_response_for_api_endpoints(): void
    {
        Route::get('/api/web-data', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->getJson('/api/web-data');

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
        Route::get('/ajax-data', function (TestWebRequest $request, TestWebAction $action) {
            return $action->run($request->toRecord());
        });

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get('/ajax-data');

        $response->assertStatus(200);
        $response->assertJson(['data' => 'ajax response']);
    }
}
