<?php

declare(strict_types=1);

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;

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

if (! function_exists('action_factory')) {
    /**
     * Create a route action that directly returns a ResponseFactory instance.
     *
     * Useful for simple routes that don't need a full Action class.
     * Perfect for health checks, simple redirects, or static views.
     *
     * @param ResponseFactory $responseFactory The response factory to return
     * @return Closure
     * 
     * @example
     * Route::get('/health', action_factory(ResponseFactory::json(['status' => 'ok'], 200)));
     * Route::get('/home', action_factory(ResponseFactory::view('pages.home')));
     * Route::get('/redirect', action_factory(ResponseFactory::redirectRoute('home')));
     * Route::get('/download', action_factory(ResponseFactory::fileDownload(storage_path('file.pdf'))));
     */
    function action_factory(ResponseFactory $responseFactory): Closure
    {
        return function () use ($responseFactory) {
            return $responseFactory->toResponse();
        };
    }
}
