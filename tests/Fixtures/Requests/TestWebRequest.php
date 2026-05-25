<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\TestWebRecord;
use AndyDefer\Records\Recordable;

final class TestWebRequest extends AbstractRequest
{
    public function toRecord(
        ?int $id = null,
        ?int $userId = null,
        ?int $castInt = null,
        ?float $castFloat = null,
        ?string $castBoolTrue = null,
        ?string $castBoolFalse = null,
    ): Recordable {
        return new TestWebRecord(
            uri: $this->path(),
            method: $this->method(),
            id: $id,
            userId: $userId,
            castInt: $castInt,
            castFloat: $castFloat,
            castBoolTrue: $castBoolTrue === 'true',
            castBoolFalse: $castBoolFalse === 'true',  // ← CORRECTION : comparer avec 'true'
            query: $this->query(),
            input: $this->input(),
            cookie: $this->cookie(),
            session: session()->all(),
            ajax: $this->ajax(),
        );
    }
}
