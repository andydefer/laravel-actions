<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Unit\Support;

use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\TestSearchData;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class ActionFactoryHelperTest extends IntegrationTestCase
{
    public function test_action_factory_returns_closure(): void
    {
        // Arrange
        $factory = ResponseFactory::json(TestSearchData::from([
            'searchQuery' => 'test',
            'currentPage' => 1
        ]), 200);

        // Act
        $closure = action_factory($factory);

        // Assert
        $this->assertIsCallable($closure);
        $this->assertInstanceOf(\Closure::class, $closure);
    }

    public function test_action_factory_with_json_response(): void
    {
        // Arrange
        Route::get('/api/test', action_factory(
            ResponseFactory::json(TestSearchData::from([
                'searchQuery' => 'Hello World',
                'currentPage' => 1
            ]), 200)
        ));

        // Act
        $response = $this->getJson('/api/test');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'searchQuery' => 'Hello World',
            'currentPage' => 1
        ]);
    }

    public function test_action_factory_with_view_response(): void
    {
        // Arrange
        Route::get('/home', action_factory(ResponseFactory::view('welcome', ['name' => 'Laravel'])));

        // Act
        $response = $this->get('/home');

        // Assert
        $response->assertStatus(200);
    }

    public function test_action_factory_with_redirect_response(): void
    {
        // Arrange
        Route::get('/from', action_factory(ResponseFactory::redirect('/to')));
        Route::get('/to', function () {
            return 'Destination';
        });

        // Act
        $response = $this->get('/from');

        // Assert
        $response->assertRedirect('/to');
        $response->assertStatus(302);
    }

    public function test_action_factory_with_named_redirect(): void
    {
        // Arrange
        Route::get('/destination', function () {
            return 'Destination';
        })->name('destination');

        Route::get('/redirect', action_factory(ResponseFactory::redirectRoute('destination')));

        // Act
        $response = $this->get('/redirect');

        // Assert
        $response->assertRedirect(route('destination'));
        $response->assertStatus(302);
    }

    public function test_action_factory_with_no_content(): void
    {
        // Arrange
        Route::delete('/resource', action_factory(ResponseFactory::noContent()));

        // Act
        $response = $this->delete('/resource');

        // Assert
        $response->assertStatus(204);
    }

    public function test_action_factory_with_text_response(): void
    {
        // Arrange
        Route::get('/robots.txt', action_factory(ResponseFactory::text("User-agent: *\nDisallow:")));

        // Act
        $response = $this->get('/robots.txt');

        // Assert
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->assertSee("User-agent: *");
    }

    public function test_action_factory_with_html_response(): void
    {
        // Arrange
        Route::get('/maintenance', action_factory(
            ResponseFactory::html('<h1>Maintenance</h1><p>Site en construction</p>', 503)
        ));

        // Act
        $response = $this->get('/maintenance');

        // Assert
        $response->assertStatus(503);
        $response->assertHeader('Content-Type', 'text/html; charset=utf-8');
        $response->assertSee('<h1>Maintenance</h1>', false);
    }

    public function test_action_factory_can_be_chained_with_laravel_methods(): void
    {
        // Arrange
        Route::get('/api/data', action_factory(
            ResponseFactory::json(TestSearchData::from([
                'searchQuery' => 'value',
                'currentPage' => 1
            ]), 200)
        ))->name('api.data')->middleware('api');

        // Act
        $response = $this->getJson('/api/data');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'searchQuery' => 'value',
            'currentPage' => 1
        ]);
    }

    public function test_action_factory_with_action_route(): void
    {
        // Arrange
        Route::get('/api/action', action_factory(
            ResponseFactory::json(TestSearchData::from([
                'searchQuery' => 'from_action',
                'currentPage' => 2
            ]), 200)
        ));

        // Act
        $response = $this->getJson('/api/action');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'searchQuery' => 'from_action',
            'currentPage' => 2
        ]);
    }

    public function test_action_factory_preserves_headers(): void
    {
        // Arrange
        Route::get('/api/headers', action_factory(
            ResponseFactory::json(TestSearchData::from([
                'searchQuery' => 'test',
                'currentPage' => 1
            ]), 200)
                ->withHeaders(['X-Custom-Header' => 'custom-value'])
        ));

        // Act
        $response = $this->getJson('/api/headers');

        // Assert
        $response->assertStatus(200);
        $response->assertHeader('X-Custom-Header', 'custom-value');
        $response->assertJson([
            'searchQuery' => 'test',
            'currentPage' => 1
        ]);
    }

    public function test_action_factory_with_custom_status(): void
    {
        // Arrange
        Route::post('/api/resource', action_factory(
            ResponseFactory::json(TestSearchData::from([
                'searchQuery' => 'created',
                'currentPage' => 1
            ]), 201)
                ->withHeaders(['Location' => '/api/resource/1'])
        ));

        // Act
        $response = $this->postJson('/api/resource');

        // Assert
        $response->assertStatus(201);
        $response->assertHeader('Location', '/api/resource/1');
        $response->assertJson([
            'searchQuery' => 'created',
            'currentPage' => 1
        ]);
    }

    public function test_action_factory_with_different_data_structures(): void
    {
        // Arrange
        Route::get('/api/search', action_factory(
            ResponseFactory::json(TestSearchData::from([
                'searchQuery' => 'laravel actions',
                'currentPage' => 5
            ]), 200)
        ));

        // Act
        $response = $this->getJson('/api/search');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'searchQuery' => 'laravel actions',
            'currentPage' => 5
        ]);
    }
}
