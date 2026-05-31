<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\CastWebRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class CastWebRequest extends AbstractRequest
{

    public function rules(): array
    {
        return [];
    }

    public function getRecord(): AbstractRecord
    {
        return CastWebRecord::from([
            'int' => $this->route('int'),
            'float' => $this->route('float'),
            'boolTrue' => $this->route('boolTrue'),
            'boolFalse' => $this->route('boolFalse'),
        ]);
    }
}
