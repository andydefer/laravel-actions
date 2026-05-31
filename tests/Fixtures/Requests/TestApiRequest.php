<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\TestApiRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class TestApiRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email'],
        ];
    }

    public function getRecord(): AbstractRecord
    {

        return TestApiRecord::from([
            'id' => $this->route('id'),
            'postId' => $this->route('postId'),
            'float' => $this->input('float'),
            'boolTrue' => $this->input('boolTrue'),
            'boolFalse' => $this->input('boolFalse'),
            'value' => $this->input('value'),
            'name' => $this->input('name'),
            'email' => $this->input('email'),
        ]);
    }
}
