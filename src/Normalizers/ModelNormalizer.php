<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Normalizers;

use AndyDefer\DomainStructures\Hydration\Converter\ScalarConverter;
use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionType;

final class ModelNormalizer implements NormalizerInterface
{
    /** @var array<string, true> */
    private array $visited = [];

    /** @var array<string, string> */
    private array $attributeTypes = [];

    /** @var array<string> */
    private array $mutatedAttributes = [];

    private ScalarConverter $scalarConverter;

    public function __construct()
    {
        $this->scalarConverter = new ScalarConverter;
    }

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
        $result = $model->attributesToArray();

        $this->loadAttributeTypes($model);
        $this->loadMutatedAttributes($model);

        foreach ($this->mutatedAttributes as $attribute) {
            $value = $model->getAttribute($attribute);
            if ($value !== null) {
                $result[$attribute] = $this->normalizeAttribute($value, $attribute);
            }
        }

        foreach ($result as $key => $value) {
            $result[$key] = $this->normalizeAttribute($value, $key);
        }

        $relations = $this->getAllRelations($model);
        foreach ($relations as $relationName) {
            if (! $model->relationLoaded($relationName)) {
                continue;
            }

            $relationValue = $this->getRelationValue($model, $relationName);

            if ($relationValue !== null) {
                $normalized = $this->normalizeValue($relationValue);
                if ($normalized !== null) {
                    $result[$relationName] = $normalized;
                }
            }
        }

        return $result;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $this->normalize($value);
        }

        if ($value instanceof Collection) {
            $result = [];
            foreach ($value as $item) {
                $normalized = $this->normalizeValue($item);
                if ($normalized !== null) {
                    $result[] = $normalized;
                }
            }

            return $result;
        }

        return $value;
    }

    private function loadAttributeTypes(Model $model): void
    {
        $casts = $model->getCasts();
        $this->attributeTypes = [];

        foreach ($casts as $key => $type) {
            $this->attributeTypes[$key] = $type;
        }
    }

    private function loadMutatedAttributes(Model $model): void
    {
        $this->mutatedAttributes = [];
        $reflection = new \ReflectionClass($model);

        $classMethods = array_map(
            fn ($method) => $method->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED)
        );

        $parentClass = $reflection->getParentClass();
        $parentMethods = [];
        if ($parentClass) {
            $parentMethods = array_map(
                fn ($method) => $method->getName(),
                $parentClass->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED)
            );
        }

        $ownMethods = array_diff($classMethods, $parentMethods);

        foreach ($ownMethods as $methodName) {
            $method = $reflection->getMethod($methodName);

            if ($this->isAttributeMethod($method)) {
                $this->mutatedAttributes[] = $methodName;

                continue;
            }

            if (str_ends_with($methodName, 'Attribute')) {
                $attributeName = substr($methodName, 0, -9);

                if (str_starts_with($methodName, 'get')) {
                    $attributeName = substr($methodName, 3, -9);
                    if ($attributeName !== '') {
                        $this->mutatedAttributes[] = Str::snake($attributeName);
                    }
                } else {
                    $this->mutatedAttributes[] = Str::snake($attributeName);
                }
            }
        }

        foreach ($model->getAppends() as $append) {
            if (! in_array($append, $this->mutatedAttributes, true)) {
                $this->mutatedAttributes[] = $append;
            }
        }
    }

    private function isAttributeMethod(ReflectionMethod $method): bool
    {
        try {
            $returnType = $method->getReturnType();
            if ($returnType === null) {
                return false;
            }

            $typeName = $this->getReturnTypeName($returnType);

            if ($typeName === null) {
                return false;
            }

            return $typeName === Attribute::class || is_subclass_of($typeName, Attribute::class);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getReturnTypeName(ReflectionType $type): ?string
    {
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof \ReflectionNamedType) {
                    return $subType->getName();
                }
            }

            return null;
        }

        if (method_exists($type, 'getTypes')) {
            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof \ReflectionNamedType) {
                    return $subType->getName();
                }
            }

            return null;
        }

        return null;
    }

    private function normalizeAttribute(mixed $value, ?string $key = null): mixed
    {
        $type = $key !== null ? ($this->attributeTypes[$key] ?? null) : null;

        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeAttribute($item), $value);
        }

        if ($value instanceof Model) {
            return $this->normalize($value);
        }

        if ($value instanceof Collection) {
            $result = [];
            foreach ($value as $item) {
                $normalized = $this->normalizeAttribute($item);
                if ($normalized !== null) {
                    $result[] = $normalized;
                }
            }

            return $result;
        }

        if ($type !== null && $this->scalarConverter->supports($type)) {
            return $this->scalarConverter->convert($value, $type, $key);
        }

        return $value;
    }

    private function getAllRelations(Model $model): array
    {
        $relations = [];
        $reflection = new \ReflectionClass($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $methodName = $method->getName();

            $skipMethods = [
                '__construct', '__call', '__get', '__set', '__isset', '__unset',
                'getAttribute', 'setAttribute', 'getRelations', 'setRelation',
                'getAttributes', 'getHidden', 'getVisible', 'getCasts', 'getTable',
                'getKeyName', 'getForeignKey', 'getQualifiedKeyName', 'getConnection',
                'newInstance', 'newCollection', 'newQuery', 'newModelQuery', 'newEloquentBuilder',
                'save', 'delete', 'forceDelete', 'restore', 'trashed',
                'resolveRelation', 'load', 'loadCount', 'loadMissing', 'loadMorph',
            ];

            if (in_array($methodName, $skipMethods)) {
                continue;
            }

            try {
                $returnType = $method->getReturnType();
                if ($returnType && $this->isRelationReturnType($returnType)) {
                    $relations[] = $methodName;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $relations;
    }

    private function isRelationReturnType(\ReflectionNamedType $returnType): bool
    {
        $relationTypes = [
            Relation::class,
            HasOne::class,
            HasOneThrough::class,
            HasMany::class,
            HasManyThrough::class,
            BelongsTo::class,
            BelongsToMany::class,
            MorphOne::class,
            MorphMany::class,
            MorphTo::class,
            MorphToMany::class,
        ];

        return in_array($returnType->getName(), $relationTypes) || Str::endsWith($returnType->getName(), 'Relation');
    }

    private function getRelationValue(Model $model, string $relationName): mixed
    {
        try {
            if ($model->relationLoaded($relationName)) {
                return $model->getRelation($relationName);
            }

            $relation = $model->$relationName();
            if ($relation instanceof Relation) {
                return $relation->getResults();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
