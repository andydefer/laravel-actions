<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Collections;

use AndyDefer\Actions\Tests\Fixtures\Data\Cars\CarData;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * @extends AbstractTypedCollection<CarData>
 */
final class CarDataCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(CarData::class);
    }
}
