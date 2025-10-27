<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

trait PaginationTrait
{
    /**
     * Transforme un LengthAwarePaginator en tableau avec pagination et liens
     */
    public function formatPagination(Request $request, LengthAwarePaginator $paginator): array
    {
        $pagination = [
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'totalItems' => $paginator->total(),
            'itemsPerPage' => $paginator->perPage(),
            'hasNext' => $paginator->hasMorePages(),
            'hasPrevious' => $paginator->currentPage() > 1,
        ];

        $links = [
            'self' => $request->fullUrl(),
            'first' => $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => 1])),
            'last' => $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => $paginator->lastPage()])),
        ];

        if ($paginator->hasMorePages()) {
            $links['next'] = $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => $paginator->currentPage() + 1]));
        }

        if ($paginator->currentPage() > 1) {
            $links['previous'] = $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => $paginator->currentPage() - 1]));
        }

        return ['pagination' => $pagination, 'links' => $links];
    }

    /**
     * Extrait les filtres de la requête
     */
    public function getFilters(Request $request): array
    {
        return $request->only(['search', 'sort', 'order', 'limit', 'page']);
    }
}
