<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Collections;

use AndyDefer\Actions\Tests\Fixtures\Records\CachedUserRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * @extends AbstractTypedCollection<CachedUserRecord>
 */
final class CachedUserRecordCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(CachedUserRecord::class);
    }
}
