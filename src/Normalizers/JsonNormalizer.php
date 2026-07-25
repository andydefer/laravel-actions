<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;

final class JsonNormalizer implements NormalizerInterface
{
    private NormalizerInterface $recursiveNormalizer;

    public function supports(mixed $value): bool
    {
        return $this->isJson($value);
    }

    public function normalize(mixed $value): mixed
    {
        if (! $this->isJson($value)) {
            return $value;
        }

        $jsonString = (string) $value;
        $decoded = json_decode($jsonString, true);

        return $this->recursiveNormalizer->normalize($decoded);
    }

    public function setRecursiveNormalizer(NormalizerInterface $normalizer): void
    {
        $this->recursiveNormalizer = $normalizer;
    }

    public function setNext(?NormalizerInterface $next): void {}

    private function isJson(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        if (empty($value)) {
            return false;
        }

        $decoded = json_decode($value, true);

        return $decoded !== null && json_last_error() === JSON_ERROR_NONE;
    }
}
