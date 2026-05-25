<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\Records\Recordable;

final class TestFormRequest extends AbstractRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

    protected function prepareForValidation(): void
    {
        // Trim all string inputs
        $this->merge([
            'name' => trim($this->input('name', '')),
            'email' => trim($this->input('email', '')),
        ]);
    }

    protected function afterValidation($validator): void
    {
        if ($this->input('age') && $this->input('age') < 18) {
            $validator->errors()->add('age', 'User must be at least 18 years old.');
        }
    }

    public function toRecord(array $urlParams = []): Recordable
    {
        return new TestUserRecord(
            id: (int) ($urlParams['id'] ?? $this->input('id', 0)),
            name: $this->input('name', ''),
            email: $this->input('email', ''),
        );
    }
}
