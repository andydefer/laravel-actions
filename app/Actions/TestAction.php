<?php

declare(strict_types=1);

namespace App\Actions;

use AndyDefer\Actions\AbstractAction;
use Illuminate\Http\JsonResponse;

final class TestAction extends AbstractAction
{
    protected function handle(): JsonResponse
    {
        // TODO: Implement your business logic here

        return $this->json([
            'message' => 'Action executed successfully',
            'data' => null,
        ]);
    }
}
