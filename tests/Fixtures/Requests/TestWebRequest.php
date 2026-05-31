<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\TestWebRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class TestWebRequest extends AbstractRequest
{
    public function getRecord(): AbstractRecord
    {
        return TestWebRecord::from([
            'uri' => $this->path(),
            'method' => $this->method(),
            'id' => $this->route('id'),
            'userId' => $this->route('userId'),
            'castInt' => $this->input('int'),
            'castFloat' => $this->input('float'),
            'castBoolTrue' => $this->input('boolTrue'),
            'castBoolFalse' => $this->input('boolFalse'),
            'query' => $this->query(),
            'input' => $this->input(),
            'cookie' => $this->cookie(),
            'session' => session()->all(),
            'ajax' => $this->ajax(),
        ]);
    }
}
