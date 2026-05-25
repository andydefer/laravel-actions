<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Controllers;

use AndyDefer\Actions\Tests\Fixtures\Actions\TestAction;
use AndyDefer\Actions\Tests\Fixtures\Requests\TestApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class TestController extends Controller
{
    public function __invoke(TestApiRequest $request, TestAction $action): JsonResponse
    {
        return $action->run($request->getUrlParams(), $request->toRecord());
    }
}
