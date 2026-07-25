<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\Services;

use AndyDefer\Actions\Services\PaginationUrlBuilderService;
use AndyDefer\Actions\Tests\Fixtures\Actions\Cars\IndexCarsAction;
use AndyDefer\Actions\Tests\Fixtures\Models\Car;
use AndyDefer\Actions\Tests\Fixtures\Requests\Cars\IndexCarsRequest;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class PaginationUrlBuilderServiceTest extends IntegrationTestCase
{
    private PaginationUrlBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PaginationUrlBuilderService::class);

        $this->createCar('Toyota', 'Camry', 2020, 'Red', 35000.00, true);
        $this->createCar('Honda', 'Civic', 2021, 'Blue', 28000.00, true);
        $this->createCar('Ford', 'Mustang', 2022, 'Yellow', 45000.00, false);
        $this->createCar('Chevrolet', 'Malibu', 2020, 'Black', 32000.00, true);
        $this->createCar('Nissan', 'Altima', 2021, 'Silver', 30000.00, true);

        Route::get('/api/v1/cars', action_route(IndexCarsRequest::class, IndexCarsAction::class))
            ->name('api.v1.cars.index');
    }

    private function createCar(string $brand, string $model, int $year, string $color, float $price, bool $isAvailable): Car
    {
        return Car::create([
            'brand' => $brand,
            'model' => $model,
            'year' => $year,
            'color' => $color,
            'price' => $price,
            'is_available' => $isAvailable,
        ]);
    }

    private function createCars(int $count, string $brandPrefix = 'Brand'): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->createCar(
                $brandPrefix.$i,
                'Model'.$i,
                2022,
                'Red',
                30000.00,
                true
            );
        }
    }

    // ==================== TESTS D'INTÉGRATION COMPLETS ====================

    public function test_full_index_cars_response_page_1(): void
    {
        $count = Car::count();
        if ($count < 10) {
            $this->createCars(10 - $count);
        }

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=1&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($content);

        $this->assertArrayHasKey('items', $content);
        $this->assertArrayHasKey('current_page', $content);
        $this->assertArrayHasKey('per_page', $content);
        $this->assertArrayHasKey('total', $content);
        $this->assertArrayHasKey('last_page', $content);
        $this->assertArrayHasKey('next_page_url', $content);
        $this->assertArrayHasKey('prev_page_url', $content);

        $this->assertEquals(1, $content['current_page']);
        $this->assertEquals(5, $content['per_page']);
        $this->assertEquals(10, $content['total']);
        $this->assertEquals(2, $content['last_page']);
        $this->assertNotNull($content['next_page_url']);
        $this->assertNull($content['prev_page_url']);

        $this->assertCount(5, $content['items']);
        $this->assertEquals('Toyota', $content['items'][0]['brand']);
        $this->assertEquals('Camry', $content['items'][0]['model']);
    }

    public function test_full_index_cars_response_page_2(): void
    {
        Car::truncate();

        $this->createCars(15);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=2&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($content);

        $this->assertEquals(2, $content['current_page']);
        $this->assertEquals(5, $content['per_page']);
        $this->assertEquals(15, $content['total']);
        $this->assertEquals(3, $content['last_page']);
        $this->assertNotNull($content['next_page_url']);
        $this->assertNotNull($content['prev_page_url']);

        $this->assertCount(5, $content['items']);
    }

    public function test_full_index_cars_response_with_query_params(): void
    {
        Car::truncate();

        $this->createCar('Toyota', 'Corolla', 2022, 'Red', 25000.00, true);
        $this->createCar('Toyota', 'RAV4', 2023, 'Blue', 32000.00, true);
        $this->createCar('Toyota', 'Highlander', 2021, 'Black', 40000.00, false);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?brand=Toyota&current_page=1&per_page=2');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($content);

        $this->assertEquals(1, $content['current_page']);
        $this->assertEquals(2, $content['per_page']);
        $this->assertEquals(3, $content['total']);
        $this->assertEquals(2, $content['last_page']);
        $this->assertNotNull($content['next_page_url']);
        $this->assertNull($content['prev_page_url']);

        $this->assertCount(2, $content['items']);
        $this->assertEquals('Toyota', $content['items'][0]['brand']);
        $this->assertEquals('Corolla', $content['items'][0]['model']);
    }

    public function test_full_index_cars_response_empty_page(): void
    {
        Car::truncate();

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=1&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($content);
        $this->assertEquals(1, $content['current_page']);
        $this->assertEquals(5, $content['per_page']);
        $this->assertEquals(0, $content['total']);
        $this->assertEquals(1, $content['last_page']);
        $this->assertNull($content['next_page_url']);
        $this->assertNull($content['prev_page_url']);
        $this->assertCount(0, $content['items']);
    }

    public function test_full_index_cars_response_with_default_pagination(): void
    {
        Car::truncate();

        $this->createCar('Test', 'Car', 2020, 'Red', 10000.00, true);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($content);

        $this->assertEquals(1, $content['current_page']);
        $this->assertEquals(5, $content['per_page']);
        $this->assertEquals(1, $content['total']);
        $this->assertEquals(1, $content['last_page']);
        $this->assertIsArray($content['items']);
        $this->assertCount(1, $content['items']);
        $this->assertEquals('Test', $content['items'][0]['brand']);
        $this->assertEquals('Car', $content['items'][0]['model']);
    }

    public function test_full_index_cars_response_with_next_page_url(): void
    {
        Car::truncate();

        $this->createCars(10);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=1&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $content['current_page']);
        $this->assertEquals(5, $content['per_page']);
        $this->assertEquals(10, $content['total']);
        $this->assertEquals(2, $content['last_page']);
        $this->assertNotNull($content['next_page_url']);
        $this->assertNull($content['prev_page_url']);

        // Vérifier que l'URL contient les bons paramètres
        $this->assertStringContainsString('current_page=2', $content['next_page_url']);
        $this->assertStringContainsString('per_page=5', $content['next_page_url']);
    }

    public function test_full_index_cars_response_with_prev_page_url(): void
    {
        Car::truncate();

        $this->createCars(10);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=2&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, $content['current_page']);
        $this->assertEquals(5, $content['per_page']);
        $this->assertEquals(10, $content['total']);
        $this->assertEquals(2, $content['last_page']);
        $this->assertNull($content['next_page_url']);
        $this->assertNotNull($content['prev_page_url']);

        // Vérifier que l'URL contient les bons paramètres
        $this->assertStringContainsString('current_page=1', $content['prev_page_url']);
        $this->assertStringContainsString('per_page=5', $content['prev_page_url']);
    }
}
