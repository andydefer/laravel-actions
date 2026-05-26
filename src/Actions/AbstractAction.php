<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Actions;

use AndyDefer\Actions\Traits\Http\SendsHttpResponses;
use AndyDefer\Records\EmptyRecord;
use AndyDefer\Records\Recordable;
use Exception;

abstract class AbstractAction
{
    use SendsHttpResponses;

    private Recordable $request;

    /**
     * Template method that defines the execution flow.
     * This method is final and cannot be overridden.
     *
     * @param  Recordable  $request  The request Record
     * @return mixed The HTTP response
     */
    final public function run(Recordable $request = new EmptyRecord): mixed
    {
        $this->request = $request;

        try {
            $this->before($request);
            $response = $this->handle($request);
            $this->after(true, null, $request);

            return $response;
        } catch (Exception $e) {
            $this->after(false, $e, $request);
            throw $e;
        }
    }

    /**
     * Hook called before the main handle() method.
     *
     * @param  Recordable  $request  The request Record
     */
    protected function before(Recordable $request): void
    {
        // Override in concrete actions
    }

    /**
     * Core business logic of the action.
     *
     * @param  Recordable  $request  The request Record
     * @return mixed The HTTP response
     */
    abstract protected function handle(Recordable $request): mixed;

    /**
     * Hook called after the main handle() method.
     *
     * @param  bool  $success  Whether the execution was successful
     * @param  Exception|null  $error  The exception if execution failed
     * @param  Recordable  $request  The request Record
     */
    protected function after(bool $success, ?Exception $error = null, Recordable $request = new EmptyRecord): void
    {
        // Override in concrete actions
    }

    /**
     * Get the request Record.
     */
    public function getRequest(): Recordable
    {
        return $this->request;
    }
}
