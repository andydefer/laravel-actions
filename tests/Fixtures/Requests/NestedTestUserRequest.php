<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class NestedTestUserRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'user.name' => ['required', 'string'],
            'user.email' => ['required', 'email'],
            'user.profile.age' => ['nullable', 'integer'],
        ];
    }

    public function getRecord(): AbstractRecord
    {
        return new class extends AbstractRecord {};
    }
}
