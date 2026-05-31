<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\CastParamsRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class CastParamsRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [];
    }

    public function getRecord(): AbstractRecord
    {
        return CastParamsRecord::from([
            'int' => (int) $this->route('int'),
            'float' => (float) $this->route('float'),
            'boolTrue' => $this->route('boolTrue') === 'true',
            'boolFalse' => $this->route('boolFalse') === 'true',
        ]);
    }
}
