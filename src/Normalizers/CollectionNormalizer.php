<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;
use Illuminate\Support\Collection;

final class CollectionNormalizer implements NormalizerInterface
{
    private NormalizerInterface $recursiveNormalizer;

    public function supports(mixed $value): bool
    {
        return $value instanceof Collection;
    }

    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof Collection) {
            return $value;
        }

        $result = [];
        foreach ($value as $item) {
            $result[] = $this->recursiveNormalizer->normalize($item);
        }

        return $result;
    }

    public function setRecursiveNormalizer(NormalizerInterface $normalizer): void
    {
        $this->recursiveNormalizer = $normalizer;
    }

    public function setNext(?NormalizerInterface $next): void {}
}
