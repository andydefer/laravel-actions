<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Contracts;

use AndyDefer\Records\Recordable;

interface ActionInterface
{
    /**
     * Execute the action for a specific HTTP route.
     *
     * @param  array<string, mixed>  $urlParams  URL parameters from the route
     * @param  Recordable  $record  The Record containing all request data
     * @return mixed The HTTP response (JsonResponse|InertiaResponse|RedirectResponse|Response)
     */
    public function run(array $urlParams, Recordable $record): mixed;
}
