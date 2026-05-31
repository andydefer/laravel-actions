<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\CastWebData;
use AndyDefer\Actions\Tests\Fixtures\Records\CastWebRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class CastWebAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var CastWebRecord $request */

        return ResponseFactory::json(new CastWebData(
            castInt: $request->int,
            castFloat: $request->float,
            castBoolTrue: $request->boolTrue,
            castBoolFalse: $request->boolFalse,
        ));
    }
}
