<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\TestApiRecord;
use AndyDefer\Records\Recordable;

final class TestApiRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email'],
        ];
    }

    public function toRecord(
        ?int $id = null,
        ?int $postId = null,
        ?float $float = null,
        ?bool $boolTrue = null,
        ?bool $boolFalse = null,
        ?string $value = null,
        ?string $name = null,
        ?string $email = null,
    ): Recordable {
        return new TestApiRecord(
            id: $id,
            postId: $postId,
            float: $float,
            boolTrue: $boolTrue,
            boolFalse: $boolFalse,
            value: $value,
            name: $name ?? $this->input('name', 'Default User'),
            email: $email ?? $this->input('email', 'default@example.com'),
        );
    }
}
