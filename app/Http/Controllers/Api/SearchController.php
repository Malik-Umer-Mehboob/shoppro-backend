<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private SearchService $searchService) {}

    /**
     * GET /api/search
     */
    public function search(Request $request)
    {
        $query   = $request->input('q', '');
        $sortBy  = $request->input('sort_by', 'relevance');
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);

        $filters = $request->only([
            'category', 'brand', 'min_price', 'max_price',
            'color', 'size', 'rating', 'discount', 'stock_status',
        ]);

        $results = $this->searchService->searchProducts($query, $filters, $sortBy, $page, $perPage);

        // Get dynamic filter options for the result set
        $availableFilters = $this->searchService->getAvailableFilters($query);

        // Log search
        if (!empty($query)) {
            $this->searchService->logSearch(
                $query,
                $results->total(),
                $filters,
                $request->user()?->id,
                $request->ip()
            );
        }

        return response()->json([
            'results'           => $results->items(),
            'pagination'        => [
                'current_page'  => $results->currentPage(),
                'last_page'     => $results->lastPage(),
                'per_page'      => $results->perPage(),
                'total'         => $results->total(),
            ],
            'available_filters' => $availableFilters,
            'query'             => $query,
            'sort_by'           => $sortBy,
            'applied_filters'   => $filters,
        ]);
    }

    /**
     * GET /api/search/autocomplete
     */
    public function autocomplete(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }

        $results = $this->searchService->getAutocompleteResults($query);

        return response()->json($results);
    }

    /**
     * GET /api/admin/search/top — Admin analytics
     */
    public function topSearches(Request $request)
    {
        $limit = (int) $request->input('limit', 20);
        $results = $this->searchService->getTopSearches($limit);

        return response()->json(['top_searches' => $results]);
    }
}
