<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Http\Requests;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Abstract base class for all HTTP Request classes in the Action system.
 *
 * This class extends Laravel's FormRequest to provide validation and authorization
 * capabilities, while adding the ability to transform validated request data into
 * a strongly-typed Record object.
 *
 * The Record acts as a type-safe data transfer object (DTO) that carries all
 * validated request data (including route parameters, query string, and request body)
 * to the Action layer.
 *
 * @example
 * final class CreateUserRequest extends AbstractRequest
 * {
 *     public function rules(): array
 *     {
 *         return [
 *             'name' => ['required', 'string', 'max:255'],
 *             'email' => ['required', 'email', 'unique:users'],
 *         ];
 *     }
 *
 *     public function getRecord(): AbstractRecord
 *     {
 *         return CreateUserRecord::from([
 *             'name' => $this->input('name'),
 *             'email' => $this->input('email'),
 *         ]);
 *     }
 * }
 *
 * @author Andy Defer
 */
abstract class AbstractRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to make this request.
     *
     * Override this method to implement custom authorization logic.
     * By default, all requests are authorized.
     *
     * @return bool True if the user is authorized, false otherwise
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Defines the validation rules that apply to the request.
     *
     * Override this method to specify validation constraints for request data.
     * The rules follow Laravel's standard validation rule syntax.
     *
     * @return array<string, array<int, string>> Associative array of field names to rule arrays
     *
     * @example
     * return [
     *     'email' => ['required', 'email'],
     *     'name' => ['required', 'string', 'max:255'],
     * ];
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Transforms the validated HTTP request into a strongly-typed Record object.
     *
     * This method must be implemented by all concrete request classes.
     * It extracts data from the request (input, route parameters, query parameters)
     * and constructs a Record that will be passed to the Action's handle() method.
     *
     * The Record provides type safety and a clear contract between the HTTP layer
     * and the business logic layer.
     *
     * @return AbstractRecord The Record containing all request data
     *
     * @example
     * public function getRecord(): AbstractRecord
     * {
     *     return CreateUserRecord::from([
     *         'name' => $this->input('name'),
     *         'email' => $this->input('email'),
     *         'ipAddress' => $this->ip(),
     *     ]);
     * }
     */
    abstract public function getRecord(): AbstractRecord;
}
