<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\EncodeData;
use AndyDefer\Actions\Tests\Fixtures\Records\EncodeRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class EncodeAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var EncodeRecord $request */

        return ResponseFactory::json(new EncodeData(
            value: $request->value,
        ));
    }
}
