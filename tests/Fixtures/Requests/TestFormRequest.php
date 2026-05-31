<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class TestFormRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'age' => ['sometimes', 'integer', 'min:0', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'age.integer' => 'Age must be a valid number.',
        ];
    }

    public function getRecord(): AbstractRecord
    {
        return TestUserRecord::from([
            'id' => (int) ($this->route('id') ?? $this->input('id', 0)),
            'name' => $this->input('name', ''),
            'email' => $this->input('email', ''),
        ]);
    }
}
