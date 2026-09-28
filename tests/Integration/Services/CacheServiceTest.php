<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Integration\Services;

use AndyDefer\Actions\Contracts\CacheServiceInterface;
use AndyDefer\Actions\Services\CacheService;
use AndyDefer\Actions\Tests\Fixtures\Collections\CachedUserRecordCollection;
use AndyDefer\Actions\Tests\Fixtures\Datas\CachedUserData;
use AndyDefer\Actions\Tests\Fixtures\Records\AnotherCachedRecord;
use AndyDefer\Actions\Tests\Fixtures\Records\CachedUserRecord;
use AndyDefer\Actions\Tests\Fixtures\ValueObjects\CachedEmailVO;
use AndyDefer\Actions\Tests\IntegrationTestCase;
use AndyDefer\DomainStructures\Utils\StrictAssociative;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;
use stdClass;

final class CacheServiceTest extends IntegrationTestCase
{
    private CacheService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(CacheServiceInterface::class);
    }

    // ==================== HELPERS ====================

    private function makeRecord(string $name = 'john'): CachedUserRecord
    {
        return CachedUserRecord::from([
            'name' => $name,
            'age' => 42,
            'email' => $name.'@example.com',
        ]);
    }

    private function makeData(string $name = 'jane'): CachedUserData
    {
        return CachedUserData::from([
            'name' => $name,
            'email' => $name.'@example.com',
        ]);
    }

    private function makeEmail(string $value = 'test@example.com'): CachedEmailVO
    {
        return CachedEmailVO::from(['value' => $value]);
    }

    private function makeCollection(): CachedUserRecordCollection
    {
        $collection = new CachedUserRecordCollection;
        $collection->add($this->makeRecord('alice'));
        $collection->add($this->makeRecord('bob'));

        return $collection;
    }

    private function makeAssociative(): StrictAssociative
    {
        return StrictAssociative::from([
            'name' => 'john',
            'age' => 42,
            'active' => true,
        ]);
    }

    // ==================== put / get (Record) ====================

    public function test_put_then_get_returns_same_record(): void
    {
        $record = $this->makeRecord('john');

        $this->service->put('user.1', $record, 60);

        $restored = $this->service->get('user.1', CachedUserRecord::class);

        $this->assertInstanceOf(CachedUserRecord::class, $restored);
        $this->assertSame('john', $restored->name);
        $this->assertSame(42, $restored->age);
        $this->assertSame('john@example.com', $restored->email);
    }

    public function test_get_returns_null_when_key_is_missing(): void
    {
        $this->assertNull($this->service->get('missing', CachedUserRecord::class));
    }

    public function test_get_returns_null_when_cached_class_is_not_subclass(): void
    {
        $this->service->put('user.1', $this->makeRecord(), 60);

        $this->assertNull($this->service->get('user.1', AnotherCachedRecord::class));
    }

    public function test_get_returns_null_when_cached_class_does_not_exist(): void
    {
        $this->app->make(CacheRepository::class)->put(
            'actions:cache:user.1',
            [
                'class' => 'App\\NonExistentClass',
                'payload' => [],
            ],
            60,
        );

        $this->assertNull($this->service->get('user.1', CachedUserRecord::class));
    }

    public function test_get_returns_null_when_payload_is_not_array(): void
    {
        $this->app->make(CacheRepository::class)->put(
            'actions:cache:user.1',
            'not-an-array',
            60,
        );

        $this->assertNull($this->service->get('user.1', CachedUserRecord::class));
    }

    // ==================== put / get (Data) ====================

    public function test_put_then_get_returns_same_data(): void
    {
        $data = $this->makeData('jane');

        $this->service->put('data.1', $data, 60);

        $restored = $this->service->get('data.1', CachedUserData::class);

        $this->assertInstanceOf(CachedUserData::class, $restored);
        $this->assertSame('jane', $restored->name);
        $this->assertSame('jane@example.com', $restored->email->getValue());
    }

    // ==================== put / get (Value Object) ====================

    public function test_put_then_get_returns_same_value_object(): void
    {
        $vo = $this->makeEmail('test@example.com');

        $this->service->put('email.1', $vo, 60);

        $restored = $this->service->get('email.1', CachedEmailVO::class);

        $this->assertInstanceOf(CachedEmailVO::class, $restored);
        $this->assertSame('test@example.com', $restored->value);
    }

    // ==================== put / get (Typed Collection) ====================

    public function test_put_then_get_returns_same_collection(): void
    {
        $collection = $this->makeCollection();

        $this->service->put('collection.1', $collection, 60);

        $restored = $this->service->get('collection.1', CachedUserRecordCollection::class);

        $this->assertInstanceOf(CachedUserRecordCollection::class, $restored);
        $this->assertCount(2, $restored);
        $this->assertSame('alice', $restored->first()->name);
        $this->assertSame('bob', $restored->last()->name);
    }

    // ==================== put / get (StrictAssociative) ====================

    public function test_put_then_get_returns_same_associative(): void
    {
        $assoc = $this->makeAssociative();

        $this->service->put('assoc.1', $assoc, 60);

        $restored = $this->service->get('assoc.1', StrictAssociative::class);

        $this->assertInstanceOf(StrictAssociative::class, $restored);
        $this->assertSame('john', $restored->get('name'));
        $this->assertSame(42, $restored->get('age'));
        $this->assertSame(true, $restored->get('active'));
    }

    // ==================== remember ====================

    public function test_remember_stores_value_on_first_call(): void
    {
        $calls = 0;

        $value = $this->service->remember('user.1', function () use (&$calls) {
            $calls++;

            return $this->makeRecord('john');
        }, 60);

        $this->assertSame(1, $calls);
        $this->assertInstanceOf(CachedUserRecord::class, $value);
        $this->assertSame('john', $value->name);
    }

    public function test_remember_returns_cached_value_on_second_call(): void
    {
        $calls = 0;

        $callback = function () use (&$calls) {
            $calls++;

            return $this->makeRecord('john');
        };

        $this->service->remember('user.1', $callback, 60);
        $this->service->remember('user.1', $callback, 60);

        $this->assertSame(1, $calls);
    }

    public function test_remember_with_data(): void
    {
        $value = $this->service->remember(
            'data.1',
            fn () => $this->makeData('jane'),
            60,
        );

        $this->assertInstanceOf(CachedUserData::class, $value);
        $this->assertSame('jane', $value->name);
    }

    public function test_remember_with_value_object(): void
    {
        $value = $this->service->remember(
            'email.1',
            fn () => $this->makeEmail('test@example.com'),
            60,
        );

        $this->assertInstanceOf(CachedEmailVO::class, $value);
        $this->assertSame('test@example.com', $value->value);
    }

    public function test_remember_with_collection(): void
    {
        $value = $this->service->remember(
            'collection.1',
            fn () => $this->makeCollection(),
            60,
        );

        $this->assertInstanceOf(CachedUserRecordCollection::class, $value);
        $this->assertCount(2, $value);
    }

    public function test_remember_with_associative(): void
    {
        $value = $this->service->remember(
            'assoc.1',
            fn () => $this->makeAssociative(),
            60,
        );

        $this->assertInstanceOf(StrictAssociative::class, $value);
        $this->assertSame('john', $value->get('name'));
    }

    public function test_remember_throws_when_callback_does_not_return_object(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->remember('user.1', fn () => 'not-an-object', 60);
    }

    public function test_remember_throws_when_callback_returns_plain_object(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->remember('user.1', fn () => new stdClass, 60);
    }

    // ==================== has / forget ====================

    public function test_has_returns_true_when_key_exists(): void
    {
        $this->service->put('user.1', $this->makeRecord(), 60);

        $this->assertTrue($this->service->has('user.1'));
    }

    public function test_has_returns_false_when_key_is_missing(): void
    {
        $this->assertFalse($this->service->has('user.1'));
    }

    public function test_forget_removes_cached_value(): void
    {
        $this->service->put('user.1', $this->makeRecord(), 60);

        $this->assertTrue($this->service->forget('user.1'));
        $this->assertFalse($this->service->has('user.1'));
    }

    // ==================== put guard ====================

    public function test_put_throws_when_value_is_not_transformable(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->put('user.1', new stdClass, 60);
    }

    // ==================== TTL ====================

    public function test_put_clamps_ttl_to_minimum_one_second(): void
    {
        $this->service->put('user.1', $this->makeRecord(), 0);

        $this->assertTrue($this->service->has('user.1'));
    }
}
