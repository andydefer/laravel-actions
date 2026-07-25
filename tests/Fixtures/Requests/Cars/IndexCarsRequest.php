<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests\Cars;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\Cars\IndexCarsRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class IndexCarsRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'current_page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function getRecord(): AbstractRecord
    {

        return IndexCarsRecord::from([
            'current_page' => (int) $this->input('current_page', 1),
            'per_page' => (int) $this->input('per_page', 5),
        ]);
    }
}
