<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Http\Requests;

use AndyDefer\Records\Recordable;
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
     * Transform the validated request into a Record object.
     *
     * This method creates a Record containing ALL the data needed by the Action:
     * - URL parameters (route parameters)
     * - Query string parameters
     * - Request body data
     * - Authenticated user information
     * - Request metadata
     *
     * @return Recordable The Record object containing all request data
     */
    abstract public function toRecord(): Recordable;
}
