<?php

declare(strict_types=1);

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Actions\AbstractAction;

if (! function_exists('action_route')) {
    /**
     * Create a route action that resolves Request and Action classes.
     *
     * @param string $requestClass FQCN of Request class (must extend AbstractRequest)
     * @param string $actionClass  FQCN of Action class (must extend AbstractAction)
     * @return Closure
     */
    function action_route(string $requestClass, string $actionClass): Closure
    {
        return function () use ($requestClass, $actionClass) {
            /** @var AbstractRequest $request */
            $request = app($requestClass);

            /** @var AbstractAction $action */
            $action = app($actionClass);

            $responseFactory = $action->run($request->getRecord());

            return $responseFactory->toResponse();
        };
    }
}
