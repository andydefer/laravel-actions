<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Http\Requests;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    /**
     * Get the Record associated with this request.
     * The Record is automatically hydrated from the request data.
     */
    abstract public function getRecord(): AbstractRecord;
}
