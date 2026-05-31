<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * @method int getId()
 * @method string getName()
 * @method string getEmail()
 */
final class TestApiRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?int $postId = null,
        public readonly ?float $float = null,
        public readonly ?bool $boolTrue = null,
        public readonly ?bool $boolFalse = null,
        public readonly ?string $value = null,
        public readonly ?string $name = 'Default User',
        public readonly ?string $email = 'default@example.com',
    ) {}
}
