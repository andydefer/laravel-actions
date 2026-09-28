<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Contracts;

use Closure;

interface CacheServiceInterface
{
    public function put(string $key, object $value, int $ttlSeconds): void;

    public function remember(string $key, Closure $callback, int $ttlSeconds): object;

    public function get(string $key, string $class): ?object;

    public function has(string $key): bool;

    public function forget(string $key): bool;
}
