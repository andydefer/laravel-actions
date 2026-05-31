<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Tests\Fixtures\Data\TestUserData;
use AndyDefer\Actions\Tests\Fixtures\Records\TestApiRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\EmptyRecord;
use Exception;

final class TestActionWithHooks extends AbstractAction
{
    public bool $beforeCalled = false;

    public bool $afterCalled = false;

    public bool $afterSuccess = false;

    public ?Exception $afterError = null;

    public bool $shouldThrow = false;

    protected function before(AbstractRecord $request): void
    {
        $this->beforeCalled = true;
    }

    protected function handle(AbstractRecord $request): ResponseFactory
    {
        if ($this->shouldThrow) {
            throw new Exception('Test exception from handle method');
        }

        /** @var TestApiRecord $request */
        $id = $request->id ?? 1;

        $userData = TestUserData::from([
            'id' => (string) $id,
            'name' => 'User '.$id,
            'email' => 'user'.$id.'@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
            'emailVerifiedAt' => null,
            'tags' => [],
            'createdAt' => now()->toIso8601ZuluString(),
        ]);

        return ResponseFactory::json($userData);
    }

    protected function after(bool $success, ?Exception $error = null, AbstractRecord $request = new EmptyRecord): void
    {
        $this->afterCalled = true;
        $this->afterSuccess = $success;
        $this->afterError = $error;
    }
}
