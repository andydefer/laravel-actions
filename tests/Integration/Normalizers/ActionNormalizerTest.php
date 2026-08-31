<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\Normalizers;

use AndyDefer\Actions\Normalizers\ActionRootNormalizer;
use AndyDefer\Actions\Tests\Fixtures\Collections\CarDataCollection;
use AndyDefer\Actions\Tests\Fixtures\Data\Cars\CarData;
use AndyDefer\Actions\Tests\Fixtures\Data\Users\UserWithCarsData;
use AndyDefer\Actions\Tests\Fixtures\Models\Car;
use AndyDefer\Actions\Tests\Fixtures\Models\User;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ActionNormalizerTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private ActionRootNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new ActionRootNormalizer;
    }

    private function createUserWithCars(string $name, string $email, int $carCount): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'gender' => 'male',
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'user_type' => 'doctor',
            'user_status' => 'active',
        ]);

        for ($i = 1; $i <= $carCount; $i++) {
            Car::create([
                'user_id' => $user->id,
                'brand' => 'Brand'.$i,
                'model' => 'Model'.$i,
                'year' => 2022,
                'color' => 'Red',
                'price' => 30000.00 + ($i * 1000),
                'is_available' => true,
            ]);
        }

        return $user;
    }

    // ==================== TESTS DE RÉCURSION AVEC CARS ====================

    public function test_normalize_user_with_cars_does_not_recurs(): void
    {
        $user = $this->createUserWithCars('John Doe', 'john@example.com', 3);

        $user->load('cars');

        $normalized = $this->normalizer->normalize($user);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertEquals($user->id, $normalized['id']);
        $this->assertEquals('John Doe', $normalized['name']);

        $this->assertArrayHasKey('cars', $normalized);
        $this->assertCount(3, $normalized['cars']);

        $firstCar = $normalized['cars'][0];
        $this->assertArrayHasKey('id', $firstCar);
        $this->assertArrayHasKey('brand', $firstCar);
        $this->assertArrayHasKey('model', $firstCar);
        $this->assertArrayHasKey('user_id', $firstCar);
        $this->assertEquals($user->id, $firstCar['user_id']);
    }

    public function test_normalize_user_with_cars_and_custom_data_class(): void
    {
        $user = $this->createUserWithCars('Jane Smith', 'jane@example.com', 2);

        $user->load('cars');

        $normalized = $this->normalizer->normalize($user);
        $userData = UserWithCarsData::from($normalized);

        $this->assertInstanceOf(UserWithCarsData::class, $userData);
        $this->assertEquals($user->id, $userData->id);
        $this->assertEquals('Jane Smith', $userData->name);
        $this->assertEquals('jane@example.com', $userData->email);

        $this->assertNotNull($userData->cars);
        $this->assertInstanceOf(CarDataCollection::class, $userData->cars);
        $this->assertCount(2, $userData->cars);

        foreach ($userData->cars as $car) {
            $this->assertInstanceOf(CarData::class, $car);
            $this->assertEquals($user->id, $car->user_id);
        }
    }

    public function test_normalize_multiple_users_with_cars_does_not_recurs(): void
    {
        $user1 = $this->createUserWithCars('Alice', 'alice@example.com', 2);
        $user2 = $this->createUserWithCars('Bob', 'bob@example.com', 3);

        $users = User::with('cars')->get();

        $normalized = $this->normalizer->normalize($users);

        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);

        $this->assertEquals($user1->id, $normalized[0]['id']);
        $this->assertEquals('Alice', $normalized[0]['name']);
        $this->assertCount(2, $normalized[0]['cars']);

        $this->assertEquals($user2->id, $normalized[1]['id']);
        $this->assertEquals('Bob', $normalized[1]['name']);
        $this->assertCount(3, $normalized[1]['cars']);
    }

    public function test_normalize_user_without_cars_returns_empty_collection(): void
    {
        $user = User::create([
            'name' => 'No Cars User',
            'email' => 'nocars@example.com',
            'password' => 'password',
            'gender' => 'male',
            'slug' => 'no-cars-user',
            'user_type' => 'doctor',
            'user_status' => 'active',
        ]);

        $user->load('cars');

        $normalized = $this->normalizer->normalize($user);
        $userData = UserWithCarsData::from($normalized);

        $this->assertInstanceOf(UserWithCarsData::class, $userData);
        $this->assertEquals('No Cars User', $userData->name);

        $this->assertNotNull($userData->cars);
        $this->assertInstanceOf(CarDataCollection::class, $userData->cars);
        $this->assertCount(0, $userData->cars);
    }

    public function test_normalize_user_with_cars_does_not_create_circular_reference(): void
    {
        $user = $this->createUserWithCars('Circular Test', 'circular@example.com', 2);

        $user->load('cars');

        $normalized = $this->normalizer->normalize($user);

        foreach ($normalized['cars'] as $car) {
            $this->assertEquals($user->id, $car['user_id']);
            $this->assertArrayNotHasKey('user', $car);
        }
    }

    public function test_normalize_user_with_cars_and_specialties_does_not_recurs(): void
    {
        $user = $this->createUserWithCars('Specialty Test', 'specialty@example.com', 2);

        $user->load('cars');

        $normalized = $this->normalizer->normalize($user);

        $this->assertIsArray($normalized);
        $this->assertEquals($user->id, $normalized['id']);
        $this->assertArrayHasKey('cars', $normalized);
        $this->assertCount(2, $normalized['cars']);
    }

    public function test_normalize_user_with_cars_converts_to_car_data_collection(): void
    {
        $user = $this->createUserWithCars('Data Collection Test', 'datacollection@example.com', 3);

        $user->load('cars');

        $normalized = $this->normalizer->normalize($user);
        $userData = UserWithCarsData::from($normalized);

        $this->assertInstanceOf(UserWithCarsData::class, $userData);
        $this->assertInstanceOf(CarDataCollection::class, $userData->cars);
        $this->assertCount(3, $userData->cars);

        foreach ($userData->cars as $car) {
            $this->assertInstanceOf(CarData::class, $car);
            $this->assertIsInt($car->id);
            $this->assertIsString($car->brand);
            $this->assertIsString($car->model);
            $this->assertIsInt($car->year);
            $this->assertIsString($car->color);
            $this->assertIsFloat($car->price);
            $this->assertIsBool($car->is_available);
        }
    }

    public function test_normalize_user_with_cars_car_data_collection_is_typed(): void
    {
        $user = $this->createUserWithCars('Typed Collection Test', 'typed@example.com', 2);

        $user->load('cars');

        $normalized = $this->normalizer->normalize($user);
        $userData = UserWithCarsData::from($normalized);

        $this->assertInstanceOf(CarDataCollection::class, $userData->cars);

        $carIds = $userData->cars
            ->map(fn (CarData $car) => $car->id)
            ->toArray();
        $this->assertCount(2, $carIds);

        $brands = $userData->cars
            ->map(fn (CarData $car) => $car->brand)
            ->toArray();
        $this->assertContains('Brand1', $brands);
        $this->assertContains('Brand2', $brands);

        foreach ($userData->cars as $car) {
            $this->assertInstanceOf(CarData::class, $car);
        }
    }

    public function test_normalize_breaks_circular_reference_with_specialty_and_user(): void
    {
        $user = $this->createUserWithCars('Circular Reference Test', 'circular@example.com', 1);
        $user->load('cars');

        $normalized = $this->normalizer->normalize($user);

        $this->assertIsArray($normalized);
        $this->assertEquals($user->id, $normalized['id']);
        $this->assertArrayHasKey('cars', $normalized);
        $this->assertCount(1, $normalized['cars']);

        $car = $normalized['cars'][0];
        $this->assertArrayHasKey('user_id', $car);
        $this->assertEquals($user->id, $car['user_id']);
        $this->assertArrayNotHasKey('user', $car);
    }

    public function test_normalize_works_with_deeply_nested_relations(): void
    {
        $user = $this->createUserWithCars('Deep Test', 'deep@example.com', 3);
        $user->load('cars');

        $normalized = $this->normalizer->normalize($user);
        $userData = UserWithCarsData::from($normalized);

        $this->assertInstanceOf(UserWithCarsData::class, $userData);
        $this->assertNotNull($userData->cars);

        foreach ($userData->cars as $car) {
            $this->assertInstanceOf(CarData::class, $car);
            $this->assertEquals($user->id, $car->user_id);
        }
    }
}
