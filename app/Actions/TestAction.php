<?php

declare(strict_types=1);

namespace App\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\DataObject;
use Illuminate\Http\JsonResponse;

final class TestAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): JsonResponse
    {
        // TODO: Implement your business logic here

        return $this->json(DataObject::from([
            'message' => 'Action executed successfully',
            'data' => null,
        ]));
    }
}
