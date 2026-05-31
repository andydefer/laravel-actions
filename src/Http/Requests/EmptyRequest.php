<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Http\Requests;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\EmptyRecord;

final class EmptyRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [];
    }

    public function getRecord(): AbstractRecord
    {
        return new EmptyRecord;
    }
}
