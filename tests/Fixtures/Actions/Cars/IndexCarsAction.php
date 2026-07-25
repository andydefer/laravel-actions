<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Actions\Cars;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\Actions\Services\PaginationUrlBuilderService;
use AndyDefer\Actions\Tests\Fixtures\Data\Cars\PaginatedCarsData;
use AndyDefer\Actions\Tests\Fixtures\Models\Car;
use AndyDefer\Actions\Tests\Fixtures\Records\Cars\IndexCarsRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\Sequential;

final class IndexCarsAction extends AbstractAction
{
    public function __construct(
        private readonly PaginationUrlBuilderService $paginationUrlBuilder,
    ) {}

    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var IndexCarsRecord $request */
        $query = Car::query();

        $paginationMeta = $this->paginationUrlBuilder->build($query, $request);

        $items = Sequential::from(action_normalizer_chain()->normalize($paginationMeta->items));

        $responseData = PaginatedCarsData::from([
            'items' => $items,
            'current_page' => $paginationMeta->current_page,
            'per_page' => $paginationMeta->per_page,
            'total' => $paginationMeta->total,
            'last_page' => $paginationMeta->last_page,
            'next_page_url' => $paginationMeta->next_page_url?->getValue(),
            'prev_page_url' => $paginationMeta->prev_page_url?->getValue(),
        ]);

        return ResponseFactory::json($responseData);
    }
}
