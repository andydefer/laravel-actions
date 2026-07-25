<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Normalizers;

use AndyDefer\DomainStructures\Normalizers\ArrayNormalizer;
use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;
use AndyDefer\DomainStructures\Normalizers\DataNormalizer;
use AndyDefer\DomainStructures\Normalizers\DataObjectNormalizer;
use AndyDefer\DomainStructures\Normalizers\DateTimeNormalizer;
use AndyDefer\DomainStructures\Normalizers\EnumNormalizer;
use AndyDefer\DomainStructures\Normalizers\NullNormalizer;
use AndyDefer\DomainStructures\Normalizers\RecordNormalizer;
use AndyDefer\DomainStructures\Normalizers\ScalarNormalizer;
use AndyDefer\DomainStructures\Normalizers\SequentialNormalizer;
use AndyDefer\DomainStructures\Normalizers\StdClassNormalizer;
use AndyDefer\DomainStructures\Normalizers\TypedCollectionNormalizer;
use AndyDefer\DomainStructures\Normalizers\ValueObjectNormalizer;
use RuntimeException;

final class ActionRootNormalizer implements NormalizerInterface
{
    /** @var array<NormalizerInterface> */
    private array $normalizers = [];

    private bool $initialized = false;

    private bool $preserveRecordCase = false;

    public function __construct(bool $preserveRecordCase = false)
    {
        $this->preserveRecordCase = $preserveRecordCase;
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $null = new NullNormalizer;
        $scalar = new ScalarNormalizer;
        $enum = new EnumNormalizer;
        $dateTime = new DateTimeNormalizer;
        $record = new RecordNormalizer($this->preserveRecordCase);
        $vo = new ValueObjectNormalizer;
        $data = new DataNormalizer;
        $collection = new TypedCollectionNormalizer;
        $dataObject = new DataObjectNormalizer;
        $sequential = new SequentialNormalizer;
        $array = new ArrayNormalizer;
        $stdClass = new StdClassNormalizer;
        $json = new JsonNormalizer;
        $illuminateCollection = new CollectionNormalizer;
        $model = new ModelNormalizer;

        $normalizers = [
            $null,
            $scalar,
            $enum,
            $dateTime,
            $record,
            $vo,
            $data,
            $collection,
            $dataObject,
            $sequential,
            $array,
            $stdClass,
            $json,
            $illuminateCollection,
            $model,
        ];

        foreach ($normalizers as $normalizer) {
            if (method_exists($normalizer, 'setRecursiveNormalizer')) {
                $normalizer->setRecursiveNormalizer($this);
            }
        }

        $this->normalizers = $normalizers;
        $this->initialized = true;
    }

    public function supports(mixed $value): bool
    {
        return true;
    }

    public function normalize(mixed $value): mixed
    {
        $this->initialize();

        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($value)) {
                return $normalizer->normalize($value);
            }
        }

        throw new RuntimeException(sprintf(
            'No normalizer found for type %s',
            is_object($value) ? $value::class : gettype($value)
        ));
    }

    public function setRecursiveNormalizer(NormalizerInterface $normalizer): void {}

    public function setNext(?NormalizerInterface $next): void {}
}
