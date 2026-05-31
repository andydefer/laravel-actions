<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\CastParamsData;
use AndyDefer\Actions\Tests\Fixtures\Records\CastParamsRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class CastParamsAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var CastParamsRecord $request */

        return ResponseFactory::json(new CastParamsData(
            int: $request->int,
            float: $request->float,
            boolTrue: $request->boolTrue,
            boolFalse: $request->boolFalse,
            name: 'User '.$request->int,
            email: 'user'.$request->int.'@example.com',
            id: (string) $request->int,
        ));
    }
}
