<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Simple model normalizer that only converts the model to array.
 * No automatic relation or attribute transformation.
 * The user is responsible for defining what to expose via $appends, $visible, or custom methods.
 */
final class ModelNormalizer implements NormalizerInterface
{
    /** @var array<string, true> */
    private array $visited = [];

    public function supports(mixed $value): bool
    {
        return $value instanceof Model;
    }

    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof Model) {
            return $value;
        }

        $key = $value::class.'#'.($value->id ?? 'new');

        // ✅ Détection de cycle
        if (isset($this->visited[$key])) {
            return null;
        }

        $this->visited[$key] = true;

        try {
            return $this->normalizeModel($value);
        } finally {
            unset($this->visited[$key]);
        }
    }

    public function setRecursiveNormalizer(NormalizerInterface $normalizer): void {}

    public function setNext(?NormalizerInterface $next): void {}

    private function normalizeModel(Model $model): array
    {
        return $model->toArray();
    }
}
