<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Services;

use AndyDefer\Actions\Contracts\CacheServiceInterface;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;

use function action_normalizer_chain;

final class CacheService implements CacheServiceInterface
{
    private const CACHE_PREFIX = 'actions:cache:';

    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function put(string $key, object $value, int $ttlSeconds): void
    {
        $this->assertTransformable($value);

        $this->cache->put(
            $this->cacheKey($key),
            [
                'class' => $value::class,
                'payload' => action_normalizer_chain(true)->normalize($value),
            ],
            max(1, $ttlSeconds),
        );
    }

    public function remember(string $key, Closure $callback, int $ttlSeconds): object
    {
        $cached = $this->get($key, Transformable::class);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();

        if (! is_object($value)) {
            throw new InvalidArgumentException(
                'CacheService only accepts objects implementing Transformable.'
            );
        }

        $this->put($key, $value, $ttlSeconds);

        return $value;
    }

    public function get(string $key, string $class): ?object
    {
        $data = $this->cache->get($this->cacheKey($key));

        if (! is_array($data) || ! isset($data['class'], $data['payload'])) {
            return null;
        }

        $cachedClass = $data['class'];

        if (! is_string($cachedClass) || ! class_exists($cachedClass)) {
            return null;
        }

        if (! is_a($cachedClass, $class, true)) {
            return null;
        }

        return $cachedClass::from($data['payload']);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($this->cacheKey($key));
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return self::CACHE_PREFIX.$key;
    }

    private function assertTransformable(object $value): void
    {
        if (! $value instanceof Transformable) {
            throw new InvalidArgumentException(sprintf(
                'CacheService only accepts instances of %s. Got: %s',
                Transformable::class,
                $value::class,
            ));
        }
    }
}
