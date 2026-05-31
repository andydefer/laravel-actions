<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\GetUserPostsData;
use AndyDefer\Actions\Tests\Fixtures\Records\GetUserPostsRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class GetUserPostsAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var GetUserPostsRecord $request */

        return ResponseFactory::json(new GetUserPostsData(
            userId: $request->userId,
            postId: $request->postId,
            message: "User {$request->userId} posts, showing post {$request->postId}",
        ));
    }
}
