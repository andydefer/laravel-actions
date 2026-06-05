<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class TestUserRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'id' => ['sometimes', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
        ];
    }

    public function getRecord(): AbstractRecord
    {
        $validated = $this->validated();

        // Accès en tant qu'objet (StrictDataObject)
        return TestUserRecord::from([
            'id' => $validated->id ?? 0,
            'name' => $validated->name,
            'email' => $validated->email,
        ]);
    }
}
