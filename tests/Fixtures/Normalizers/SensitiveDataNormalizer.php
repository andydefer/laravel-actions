<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;

final class SensitiveDataNormalizer implements NormalizerInterface
{
    private NormalizerInterface $recursiveNormalizer;

    /** @var array<string, array<string>> */
    private array $sensitiveFields = [
        'User' => ['email', 'phone', 'password'],
        'DoctorProfile' => ['license_number', 'bio'],
    ];

    public function supports(mixed $value): bool
    {

        return is_array($value) && isset($value['user_type']);
    }

    public function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $fieldsToRemove = $this->sensitiveFields['User'] ?? [];

        foreach ($fieldsToRemove as $field) {
            unset($value[$field]);
        }

        return $value;
    }

    public function setRecursiveNormalizer(NormalizerInterface $normalizer): void
    {
        $this->recursiveNormalizer = $normalizer;
    }

    public function setNext(?NormalizerInterface $next): void {}
}
