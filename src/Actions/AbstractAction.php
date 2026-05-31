<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Actions;

use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\EmptyRecord;
use Exception;

/**
 * Base class for all Action classes following the Template Method pattern.
 *
 * An Action encapsulates the business logic for a single HTTP route.
 * It provides a consistent lifecycle with before/after hooks and automatic
 * exception handling.
 *
 * @template TRecord of AbstractRecord
 *
 * @example
 * final class CreateUserAction extends AbstractAction
 * {
 *     public function __construct(
 *         private readonly UserRepositoryInterface $userRepository
 *     ) {}
 *     
 *     protected function handle(AbstractRecord $request): ResponseFactory
 *     {
 *         $user = $this->userRepository->create($request->toArray());
 *         
 *         return ResponseFactory::json(UserData::from($user), 201);
 *     }
 * }
 *
 * @author Andy Defer
 */
abstract class AbstractAction
{
    private AbstractRecord $recordRequest;

    /**
     * Executes the action with the given record request.
     *
     * This is the main entry point and follows the Template Method pattern.
     * The method orchestrates the lifecycle: before() → handle() → after().
     * If an exception occurs during handle(), the after() hook is still called
     * with the error details before re-throwing the exception.
     *
     * @param AbstractRecord $recordRequest The validated request data as a Record object
     *
     * @return ResponseFactory The HTTP response factory ready to be converted to a response
     *
     * @throws Exception Any exception thrown during the handle() method
     */
    final public function run(AbstractRecord $recordRequest = new EmptyRecord): ResponseFactory
    {
        $this->recordRequest = $recordRequest;

        try {
            $this->before($recordRequest);
            $response = $this->handle($recordRequest);
            $this->after(true, null, $recordRequest);

            return $response;
        } catch (Exception $e) {
            $this->after(false, $e, $recordRequest);
            throw $e;
        }
    }

    /**
     * Hook called before the main business logic execution.
     *
     * Override this method to add pre-processing logic such as:
     * - Authorization checks
     * - Input sanitization
     * - Logging
     * - Precondition validation
     *
     * @param AbstractRecord $recordRequest The request record
     */
    protected function before(AbstractRecord $recordRequest): void
    {
        // Override in concrete actions
    }

    /**
     * Contains the core business logic of the action.
     *
     * This method must be implemented by all concrete actions.
     * It receives the validated request record and must return a ResponseFactory
     * configured with the appropriate HTTP response.
     *
     * @param AbstractRecord $recordRequest The request record containing validated data
     *
     * @return ResponseFactory The HTTP response factory
     */
    abstract protected function handle(AbstractRecord $recordRequest): ResponseFactory;

    /**
     * Hook called after the main business logic execution, whether successful or not.
     *
     * Override this method to add post-processing logic such as:
     * - Logging success/failure
     * - Sending notifications
     * - Recording metrics
     * - Cleaning up resources
     *
     * @param bool             $success       Whether the execution was successful
     * @param Exception|null   $error         The exception if execution failed, null otherwise
     * @param AbstractRecord   $recordRequest The request record
     */
    protected function after(bool $success, ?Exception $error = null, AbstractRecord $recordRequest = new EmptyRecord): void
    {
        // Override in concrete actions
    }

    /**
     * Retrieves the request record that was passed to the action.
     *
     * Useful for accessing request data outside the lifecycle methods,
     * such as in test assertions.
     *
     * @return AbstractRecord The request record
     */
    public function getRecordRequest(): AbstractRecord
    {
        return $this->recordRequest;
    }
}
