<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUserData;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\Actions\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\Actions\Tests\Fixtures\Records\TestApiRecord;
use AndyDefer\Records\EmptyRecord;
use AndyDefer\Records\Recordable;
use Exception;
use Illuminate\Http\JsonResponse;

final class TestActionWithHooks extends AbstractAction
{
    public bool $beforeCalled = false;

    public bool $afterCalled = false;

    public bool $afterSuccess = false;

    public ?Exception $afterError = null;

    public bool $shouldThrow = false;

    protected function before(Recordable $request): void
    {
        $this->beforeCalled = true;
    }

    protected function handle(Recordable $request): JsonResponse
    {
        if ($this->shouldThrow) {
            throw new Exception('Test exception from handle method');
        }

        /** @var TestApiRecord $request */
        $id = $request->id ?? 1;

        return $this->json(new TestUserData(
            id: (string) $id,
            name: 'User '.$id,
            email: 'user'.$id.'@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: [],
            createdAt: now()->toIso8601ZuluString(),
        ));
    }

    protected function after(bool $success, ?Exception $error = null, Recordable $request = new EmptyRecord): void
    {
        $this->afterCalled = true;
        $this->afterSuccess = $success;
        $this->afterError = $error;
    }
}
