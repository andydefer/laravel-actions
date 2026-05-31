<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUserData;
use AndyDefer\Actions\Tests\Fixtures\Records\TestApiRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class TestAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var TestApiRecord $request */
        $id = $request->id ?? 1;
        $name = $request->name ?? 'Test User '.$id;
        $email = $request->email ?? 'test'.$id.'@example.com';

        $userData = TestUserData::from([
            'id' => (string) $id,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
            'emailVerifiedAt' => null,
            'tags' => ['test', 'fixture'],
            'createdAt' => now()->toIso8601ZuluString(),
        ]);

        return ResponseFactory::json($userData);
    }
}
