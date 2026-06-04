<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Support;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\Requests\AbstractRequest;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

/**
 * Route registrar for Action-based controllers.
 *
 * Provides a fluent interface to register routes that map HTTP requests to Action classes.
 * Each route associates a Request class (for validation and data capture) with an Action class
 * (for business logic execution).
 *
 * @example
 * ActionRoute::get('/api/users/{id}', GetUserRequest::class, GetUserAction::class);
 * ActionRoute::post('/api/users', CreateUserRequest::class, CreateUserAction::class);
 *
 * @author Andy Defer
 * @deprecated Use action_route() helper function instead.
 * @see action_route()
 */
final class ActionRoute
{
    /**
     * Registers a GET route with an associated Request and Action.
     *
     * @param  string  $uri  The route URI pattern
     * @param  string  $requestClass  FQCN of the Request class (must extend AbstractRequest)
     * @param  string  $actionClass  FQCN of the Action class (must extend AbstractAction)
     *
     * @throws InvalidArgumentException When requestClass or actionClass is invalid
     * @deprecated Use Route::get($uri, action_route($requestClass, $actionClass)) instead
     */
    public static function get(string $uri, string $requestClass, string $actionClass): void
    {
        self::register('GET', $uri, $requestClass, $actionClass);
    }

    /**
     * Registers a POST route with an associated Request and Action.
     *
     * @param  string  $uri  The route URI pattern
     * @param  string  $requestClass  FQCN of the Request class (must extend AbstractRequest)
     * @param  string  $actionClass  FQCN of the Action class (must extend AbstractAction)
     *
     * @throws InvalidArgumentException When requestClass or actionClass is invalid
     * @deprecated Use Route::post($uri, action_route($requestClass, $actionClass)) instead
     */
    public static function post(string $uri, string $requestClass, string $actionClass): void
    {
        self::register('POST', $uri, $requestClass, $actionClass);
    }

    /**
     * Registers a PUT route with an associated Request and Action.
     *
     * @param  string  $uri  The route URI pattern
     * @param  string  $requestClass  FQCN of the Request class (must extend AbstractRequest)
     * @param  string  $actionClass  FQCN of the Action class (must extend AbstractAction)
     *
     * @throws InvalidArgumentException When requestClass or actionClass is invalid
     * @deprecated Use Route::put($uri, action_route($requestClass, $actionClass)) instead
     */
    public static function put(string $uri, string $requestClass, string $actionClass): void
    {
        self::register('PUT', $uri, $requestClass, $actionClass);
    }

    /**
     * Registers a PATCH route with an associated Request and Action.
     *
     * @param  string  $uri  The route URI pattern
     * @param  string  $requestClass  FQCN of the Request class (must extend AbstractRequest)
     * @param  string  $actionClass  FQCN of the Action class (must extend AbstractAction)
     *
     * @throws InvalidArgumentException When requestClass or actionClass is invalid
     * @deprecated Use Route::patch($uri, action_route($requestClass, $actionClass)) instead
     */
    public static function patch(string $uri, string $requestClass, string $actionClass): void
    {
        self::register('PATCH', $uri, $requestClass, $actionClass);
    }

    /**
     * Registers a DELETE route with an associated Request and Action.
     *
     * @param  string  $uri  The route URI pattern
     * @param  string  $requestClass  FQCN of the Request class (must extend AbstractRequest)
     * @param  string  $actionClass  FQCN of the Action class (must extend AbstractAction)
     *
     * @throws InvalidArgumentException When requestClass or actionClass is invalid
     * @deprecated Use Route::delete($uri, action_route($requestClass, $actionClass)) instead
     */
    public static function delete(string $uri, string $requestClass, string $actionClass): void
    {
        self::register('DELETE', $uri, $requestClass, $actionClass);
    }

    /**
     * Registers a route that responds to multiple HTTP methods.
     *
     * @param  array<string>  $methods  Array of HTTP methods (e.g., ['GET', 'POST'])
     * @param  string  $uri  The route URI pattern
     * @param  string  $requestClass  FQCN of the Request class (must extend AbstractRequest)
     * @param  string  $actionClass  FQCN of the Action class (must extend AbstractAction)
     *
     * @throws InvalidArgumentException When requestClass or actionClass is invalid
     * @deprecated Use Route::match($methods, $uri, action_route($requestClass, $actionClass)) instead
     */
    public static function match(array $methods, string $uri, string $requestClass, string $actionClass): void
    {
        self::register($methods, $uri, $requestClass, $actionClass);
    }

    /**
     * Registers a route that responds to all standard HTTP methods.
     *
     * @param  string  $uri  The route URI pattern
     * @param  string  $requestClass  FQCN of the Request class (must extend AbstractRequest)
     * @param  string  $actionClass  FQCN of the Action class (must extend AbstractAction)
     *
     * @throws InvalidArgumentException When requestClass or actionClass is invalid
     * @deprecated Use Route::any($uri, action_route($requestClass, $actionClass)) instead
     */
    public static function any(string $uri, string $requestClass, string $actionClass): void
    {
        self::register(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $requestClass, $actionClass);
    }

    /**
     * Validates that a class is a valid Request class.
     *
     * @param  string  $requestClass  The class name to validate
     *
     * @throws InvalidArgumentException If the class doesn't exist or doesn't extend AbstractRequest
     */
    private static function validateRequestClass(string $requestClass): void
    {
        if (! class_exists($requestClass)) {
            throw new InvalidArgumentException(
                sprintf('Request class "%s" does not exist', $requestClass)
            );
        }

        if (! is_subclass_of($requestClass, AbstractRequest::class)) {
            throw new InvalidArgumentException(
                sprintf('Request class "%s" must extend %s', $requestClass, AbstractRequest::class)
            );
        }
    }

    /**
     * Validates that a class is a valid Action class.
     *
     * @param  string  $actionClass  The class name to validate
     *
     * @throws InvalidArgumentException If the class doesn't exist or doesn't extend AbstractAction
     */
    private static function validateActionClass(string $actionClass): void
    {
        if (! class_exists($actionClass)) {
            throw new InvalidArgumentException(
                sprintf('Action class "%s" does not exist', $actionClass)
            );
        }

        if (! is_subclass_of($actionClass, AbstractAction::class)) {
            throw new InvalidArgumentException(
                sprintf('Action class "%s" must extend %s', $actionClass, AbstractAction::class)
            );
        }
    }

    /**
     * Registers the route with Laravel's Router.
     *
     * @param  string|array<string>  $method  HTTP method(s) for the route
     * @param  string  $uri  The route URI pattern
     * @param  string  $requestClass  FQCN of the Request class
     * @param  string  $actionClass  FQCN of the Action class
     *
     * @throws InvalidArgumentException When requestClass or actionClass is invalid
     */
    private static function register(string|array $method, string $uri, string $requestClass, string $actionClass): void
    {
        self::validateRequestClass($requestClass);
        self::validateActionClass($actionClass);

        Route::match((array) $method, $uri, function () use ($requestClass, $actionClass) {
            /** @var AbstractRequest $request */
            $request = app($requestClass);

            /** @var AbstractAction $action */
            $action = app($actionClass);

            $responseFactory = $action->run($request->getRecord());

            return $responseFactory->toResponse();
        });
    }
}
