<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Services;

use AndyDefer\Actions\Datas\PaginationData;
use AndyDefer\DomainStructures\Utils\Sequential;
use AndyDefer\PhpClient\ValueObjects\UrlVO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class PaginationBuilderService
{
    public function build(Builder $query): PaginationData
    {
        $perPage = (int) request()->input('per_page', 5);
        $currentPage = (int) request()->input('current_page', 1);

        $paginator = $query->paginate($perPage, ['*'], 'page', $currentPage);

        $nextPageUrl = $this->resolveNextPageUrl($paginator, $perPage);
        $prevPageUrl = $this->resolvePrevPageUrl($paginator, $perPage);

        return $this->buildResponse($paginator, $nextPageUrl, $prevPageUrl);
    }

    private function resolveNextPageUrl(LengthAwarePaginator $paginator, int $perPage): ?UrlVO
    {
        if (! $paginator->hasMorePages()) {
            return null;
        }

        return $this->buildUrl($paginator->currentPage() + 1, $perPage);
    }

    private function resolvePrevPageUrl(LengthAwarePaginator $paginator, int $perPage): ?UrlVO
    {
        $previousPage = $paginator->currentPage() - 1;

        if ($previousPage < 1) {
            return null;
        }

        return $this->buildUrl($previousPage, $perPage);
    }

    private function buildUrl(int $page, int $perPage): UrlVO
    {
        $route = request()->route();

        if ($route === null) {
            throw new \RuntimeException('Unable to build pagination URL: no route found.');
        }

        $url = route(
            $route->getName(),
            array_merge(
                $route->parameters(),
                request()->query(),
                ['current_page' => $page, 'per_page' => $perPage]
            )
        );

        return new UrlVO($url);
    }

    private function buildResponse(LengthAwarePaginator $paginator, ?UrlVO $nextPageUrl, ?UrlVO $prevPageUrl): PaginationData
    {
        $items = action_normalizer_chain(true)->normalize($paginator->items());

        return new PaginationData(
            items: Sequential::from($items),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
            nextPageUrl: $nextPageUrl,
            prevPageUrl: $prevPageUrl,
        );
    }
}
