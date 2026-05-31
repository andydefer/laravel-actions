<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\EncodeRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class EncodeRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [];
    }

    public function getRecord(): AbstractRecord
    {
        /*
            return new EncodeRecord(
                value: urldecode($this->route('value'))
            );
        */
        return EncodeRecord::from([
            'value' => urldecode($this->route('value')),
        ]);
    }
}
