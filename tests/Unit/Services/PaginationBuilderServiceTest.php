<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\Services;

use AndyDefer\Actions\Services\PaginationBuilderService;
use AndyDefer\Actions\Tests\Fixtures\Actions\Cars\IndexCarsAction;
use AndyDefer\Actions\Tests\Fixtures\Data\Cars\CarData;
use AndyDefer\Actions\Tests\Fixtures\Models\Car;
use AndyDefer\Actions\Tests\Fixtures\Requests\Cars\IndexCarsRequest;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class PaginationBuilderServiceTest extends IntegrationTestCase
{
    private PaginationBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PaginationBuilderService::class);

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

    // ==================== TESTS AVEC CLASSE D'ITEM ====================

    public function test_build_with_item_class_returns_pagination_with_transformed_items(): void
    {
        Car::truncate();

        $this->createCars(10);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=1&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($content);

        $this->assertArrayHasKey('items', $content);
        $this->assertArrayHasKey('currentPage', $content);
        $this->assertArrayHasKey('perPage', $content);
        $this->assertArrayHasKey('total', $content);
        $this->assertArrayHasKey('lastPage', $content);

        $this->assertEquals(1, $content['currentPage']);
        $this->assertEquals(5, $content['perPage']);
        $this->assertEquals(10, $content['total']);
        $this->assertEquals(2, $content['lastPage']);

        $this->assertCount(5, $content['items']);

        // Vérifier que les items sont des CarData
        foreach ($content['items'] as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('brand', $item);
            $this->assertArrayHasKey('model', $item);
            $this->assertArrayHasKey('year', $item);
            $this->assertArrayHasKey('color', $item);
            $this->assertArrayHasKey('price', $item);
            $this->assertArrayHasKey('is_available', $item);
            $this->assertArrayHasKey('created_at', $item);
            $this->assertArrayHasKey('updated_at', $item);
        }
    }

    public function test_build_with_item_class_returns_correct_item_types(): void
    {
        Car::truncate();

        $this->createCar('BMW', 'X5', 2022, 'White', 60000.00, true);
        $this->createCar('Mercedes', 'C-Class', 2021, 'Black', 55000.00, true);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=1&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertCount(2, $content['items']);

        $firstItem = $content['items'][0];
        $this->assertEquals('BMW', $firstItem['brand']);
        $this->assertEquals('X5', $firstItem['model']);
        $this->assertEquals(2022, $firstItem['year']);
        $this->assertEquals('White', $firstItem['color']);
        $this->assertEquals(60000.00, $firstItem['price']);
        $this->assertTrue($firstItem['is_available']);

        $secondItem = $content['items'][1];
        $this->assertEquals('Mercedes', $secondItem['brand']);
        $this->assertEquals('C-Class', $secondItem['model']);
        $this->assertEquals(2021, $secondItem['year']);
        $this->assertEquals('Black', $secondItem['color']);
        $this->assertEquals(55000.00, $secondItem['price']);
        $this->assertTrue($secondItem['is_available']);
    }

    public function test_build_with_item_class_on_empty_result(): void
    {
        Car::truncate();

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=1&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(1, $content['currentPage']);
        $this->assertEquals(5, $content['perPage']);
        $this->assertEquals(0, $content['total']);
        $this->assertEquals(1, $content['lastPage']);
        $this->assertCount(0, $content['items']);
        $this->assertNull($content['nextPageUrl']);
        $this->assertNull($content['prevPageUrl']);
    }

    public function test_build_with_item_class_preserves_pagination_metadata(): void
    {
        Car::truncate();

        $this->createCars(15);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=2&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(2, $content['currentPage']);
        $this->assertEquals(5, $content['perPage']);
        $this->assertEquals(15, $content['total']);
        $this->assertEquals(3, $content['lastPage']);
        $this->assertNotNull($content['nextPageUrl']);
        $this->assertNotNull($content['prevPageUrl']);
        $this->assertCount(5, $content['items']);

        $this->assertStringContainsString('current_page=3', $content['nextPageUrl']);
        $this->assertStringContainsString('per_page=5', $content['nextPageUrl']);
        $this->assertStringContainsString('current_page=1', $content['prevPageUrl']);
        $this->assertStringContainsString('per_page=5', $content['prevPageUrl']);
    }

    public function test_build_with_item_class_respects_per_page(): void
    {
        Car::truncate();

        $this->createCars(20);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=1&per_page=10');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(1, $content['currentPage']);
        $this->assertEquals(10, $content['perPage']);
        $this->assertEquals(20, $content['total']);
        $this->assertEquals(2, $content['lastPage']);
        $this->assertCount(10, $content['items']);
        $this->assertNotNull($content['nextPageUrl']);
        $this->assertNull($content['prevPageUrl']);

        $this->assertStringContainsString('per_page=10', $content['nextPageUrl']);
    }

    // ==================== TESTS D'INTÉGRATION COMPLETS EXISTANTS ====================

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
        $this->assertArrayHasKey('currentPage', $content);
        $this->assertArrayHasKey('perPage', $content);
        $this->assertArrayHasKey('total', $content);
        $this->assertArrayHasKey('lastPage', $content);
        $this->assertArrayHasKey('nextPageUrl', $content);
        $this->assertArrayHasKey('prevPageUrl', $content);

        $this->assertEquals(1, $content['currentPage']);
        $this->assertEquals(5, $content['perPage']);
        $this->assertEquals(10, $content['total']);
        $this->assertEquals(2, $content['lastPage']);
        $this->assertNotNull($content['nextPageUrl']);
        $this->assertNull($content['prevPageUrl']);

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

        $this->assertEquals(2, $content['currentPage']);
        $this->assertEquals(5, $content['perPage']);
        $this->assertEquals(15, $content['total']);
        $this->assertEquals(3, $content['lastPage']);
        $this->assertNotNull($content['nextPageUrl']);
        $this->assertNotNull($content['prevPageUrl']);

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

        $this->assertEquals(1, $content['currentPage']);
        $this->assertEquals(2, $content['perPage']);
        $this->assertEquals(3, $content['total']);
        $this->assertEquals(2, $content['lastPage']);
        $this->assertNotNull($content['nextPageUrl']);
        $this->assertNull($content['prevPageUrl']);

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
        $this->assertEquals(1, $content['currentPage']);
        $this->assertEquals(5, $content['perPage']);
        $this->assertEquals(0, $content['total']);
        $this->assertEquals(1, $content['lastPage']);
        $this->assertNull($content['nextPageUrl']);
        $this->assertNull($content['prevPageUrl']);
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

        $this->assertEquals(1, $content['currentPage']);
        $this->assertEquals(5, $content['perPage']);
        $this->assertEquals(1, $content['total']);
        $this->assertEquals(1, $content['lastPage']);
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
        $this->assertEquals(1, $content['currentPage']);
        $this->assertEquals(5, $content['perPage']);
        $this->assertEquals(10, $content['total']);
        $this->assertEquals(2, $content['lastPage']);
        $this->assertNotNull($content['nextPageUrl']);
        $this->assertNull($content['prevPageUrl']);

        $this->assertStringContainsString('current_page=2', $content['nextPageUrl']);
        $this->assertStringContainsString('per_page=5', $content['nextPageUrl']);
    }

    public function test_full_index_cars_response_with_prev_page_url(): void
    {
        Car::truncate();

        $this->createCars(10);

        $this->app['config']->set('app.url', 'http://localhost');

        $response = $this->get('/api/v1/cars?current_page=2&per_page=5');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, $content['currentPage']);
        $this->assertEquals(5, $content['perPage']);
        $this->assertEquals(10, $content['total']);
        $this->assertEquals(2, $content['lastPage']);
        $this->assertNull($content['nextPageUrl']);
        $this->assertNotNull($content['prevPageUrl']);

        $this->assertStringContainsString('current_page=1', $content['prevPageUrl']);
        $this->assertStringContainsString('per_page=5', $content['prevPageUrl']);
    }
}
