<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Http\Requests;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\EmptyRecord;

/**
 * Request class for routes that require no input data.
 *
 * This request is useful for:
 * - Health check endpoints (/health, /ping)
 * - Simple GET endpoints without parameters
 * - Webhook endpoints that only need to trigger an action
 *
 * The EmptyRequest always returns an EmptyRecord, which is a Record
 * with no properties. This satisfies the AbstractRequest contract
 * without requiring a custom Record class.
 *
 * @example
 * // Route without any input data
 * ActionRoute::get('/health', EmptyRequest::class, HealthCheckAction::class);
 *
 * // Ping endpoint
 * ActionRoute::get('/ping', EmptyRequest::class, PingAction::class);
 *
 * @author Andy Defer
 */
final class EmptyRequest extends AbstractRequest
{
    /**
     * Defines validation rules for the request.
     *
     * Since this request handles no input data, no validation rules are needed.
     *
     * @return array<string, array<int, string>> Empty array (no validation)
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Transforms the request into a Record object.
     *
     * Returns an EmptyRecord, which is a typed Record with no properties.
     * This allows the Action to satisfy the AbstractRecord type hint
     * without receiving any actual data.
     *
     * @return AbstractRecord The EmptyRecord instance
     */
    public function getRecord(): AbstractRecord
    {
        return new EmptyRecord;
    }
}
