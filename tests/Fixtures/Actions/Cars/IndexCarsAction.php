<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions\Cars;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Services\PaginationBuilderService;
use AndyDefer\Actions\Tests\Fixtures\Models\Car;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class IndexCarsAction extends AbstractAction
{
    public function __construct(
        private readonly PaginationBuilderService $paginationUrlBuilder,
    ) {}

    protected function handle(AbstractRecord $request): ResponseFactory
    {
        $query = Car::query();

        $paginationMeta = $this->paginationUrlBuilder->build($query);

        return ResponseFactory::json($paginationMeta);
    }
}
