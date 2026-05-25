<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUserData;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\Actions\Tests\Fixtures\Records\TestApiRecord;
use AndyDefer\Records\Recordable;
use Illuminate\Http\JsonResponse;

final class TestApiAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        /** @var TestApiRecord $request */

        $id = $request->id ?? 1;
        $name = $request->name ?? 'User ' . $id;
        $email = $request->email ?? 'user' . $id . '@example.com';

        return $this->json(new TestUserData(
            id: (string) $id,
            name: $name,
            email: $email,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: [],
            createdAt: now()->toIso8601ZuluString(),
        ));
    }
}
