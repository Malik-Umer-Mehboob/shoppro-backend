<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Search products with advanced filtering, sorting, and pagination.
     */
    public function searchProducts(string $query, array $filters, string $sortBy = 'relevance', int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $q = Product::query()
            ->where('status', 'published')
            ->with(['category', 'images', 'variants', 'activeDiscount', 'seller']);

        // --- Full-text search ---
        if (!empty($query)) {
            $searchTerms = $this->parseSearchQuery($query);

            $q->where(function ($builder) use ($searchTerms) {
                foreach ($searchTerms['include'] as $term) {
                    $like = '%' . $term . '%';
                    $builder->where(function ($sub) use ($like) {
                        $sub->where('name', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('short_description', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhere('brand', 'like', $like)
                            ->orWhere('search_keywords', 'like', $like);
                    });
                }

                // Exclude terms prefixed with -
                foreach ($searchTerms['exclude'] as $term) {
                    $like = '%' . $term . '%';
                    $builder->where('name', 'not like', $like)
                            ->where('description', 'not like', $like);
                }
            });
        }

        // --- Filters ---
        if (!empty($filters['category'])) {
            $categoryIds = $this->getCategoryIdsWithChildren($filters['category']);
            $q->whereIn('category_id', $categoryIds);
        }

        if (!empty($filters['brand'])) {
            $brands = is_array($filters['brand']) ? $filters['brand'] : explode(',', $filters['brand']);
            $q->whereIn('brand', $brands);
        }

        if (isset($filters['min_price'])) {
            $q->where(DB::raw('COALESCE(sale_price, price)'), '>=', (float) $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $q->where(DB::raw('COALESCE(sale_price, price)'), '<=', (float) $filters['max_price']);
        }

        if (!empty($filters['color'])) {
            $colors = is_array($filters['color']) ? $filters['color'] : explode(',', $filters['color']);
            $q->whereHas('variants', fn($v) => $v->whereIn('color', $colors));
        }

        if (!empty($filters['size'])) {
            $sizes = is_array($filters['size']) ? $filters['size'] : explode(',', $filters['size']);
            $q->whereHas('variants', fn($v) => $v->whereIn('size', $sizes));
        }

        if (!empty($filters['discount'])) {
            $minDiscount = (int) $filters['discount'];
            $q->whereNotNull('sale_price')
              ->whereColumn('sale_price', '<', 'price')
              ->whereRaw('((price - sale_price) / price) * 100 >= ?', [$minDiscount]);
        }

        if (isset($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $q->where('stock_quantity', '>', 0);
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $q->where('stock_quantity', '<=', 0);
            }
        }

        // --- Sorting ---
        switch ($sortBy) {
            case 'price_low':
                $q->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $q->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'newest':
                $q->orderBy('created_at', 'desc');
                break;
            case 'relevance':
            default:
                if (!empty($query)) {
                    // Prioritize exact name matches
                    $q->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", ['%' . $query . '%']);
                }
                $q->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
                break;
        }

        return $q->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get autocomplete suggestions.
     */
    public function getAutocompleteResults(string $query): array
    {
        $like = '%' . $query . '%';

        $products = Product::where('status', 'published')
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('brand', 'like', $like)
                  ->orWhere('sku', 'like', $like);
            })
            ->select('id', 'name', 'slug', 'price', 'sale_price', 'thumbnail', 'brand')
            ->limit(5)
            ->get();

        $categories = Category::where('is_active', true)
            ->where('name', 'like', $like)
            ->select('id', 'name', 'slug')
            ->limit(3)
            ->get();

        return [
            'products'    => $products,
            'categories'  => $categories,
            'suggestions' => $this->getRelatedKeywords($query),
            'did_you_mean' => $this->getDidYouMean($query),
        ];
    }

    /**
     * Get "Did you mean?" suggestion for misspelled queries.
     */
    public function getDidYouMean(string $query): ?string
    {
        // Simple implementation: check against top successful searches
        $successfulQueries = SearchLog::where('results_count', '>', 0)
            ->select('query')
            ->groupBy('query')
            ->having(DB::raw('count(*)'), '>', 2)
            ->pluck('query');

        $bestMatch = null;
        $shortestDistance = 3; // Max distance for correction

        foreach ($successfulQueries as $sQuery) {
            $dist = levenshtein($query, $sQuery);
            if ($dist > 0 && $dist < $shortestDistance) {
                $bestMatch = $sQuery;
                $shortestDistance = $dist;
            }
        }

        return $bestMatch;
    }

    /**
     * Get related keyword suggestions.
     */
    public function getRelatedKeywords(string $query): array
    {
        return SearchLog::where('query', 'like', '%' . $query . '%')
            ->where('query', '!=', $query)
            ->select('query', DB::raw('count(*) as total'))
            ->groupBy('query')
            ->orderByDesc('total')
            ->limit(4)
            ->pluck('query')
            ->toArray();
    }

    /**
     * Get available filter options based on current result set.
     */
    public function getAvailableFilters(string $query = ''): array
    {
        $baseQuery = Product::where('status', 'published');

        if (!empty($query)) {
            $like = '%' . $query . '%';
            $baseQuery->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhere('brand', 'like', $like)
                  ->orWhere('search_keywords', 'like', $like);
            });
        }

        // Categories with counts
        $categories = (clone $baseQuery)
            ->select('category_id', DB::raw('count(*) as count'))
            ->groupBy('category_id')
            ->with('category:id,name,slug,parent_id')
            ->get()
            ->map(fn($item) => [
                'id'    => $item->category_id,
                'name'  => $item->category?->name,
                'slug'  => $item->category?->slug,
                'count' => $item->count,
            ])
            ->filter(fn($item) => $item['name'] !== null)
            ->values();

        // Brands
        $brands = (clone $baseQuery)
            ->whereNotNull('brand')
            ->select('brand', DB::raw('count(*) as count'))
            ->groupBy('brand')
            ->orderByDesc('count')
            ->get();

        // Price range
        $priceRange = (clone $baseQuery)
            ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

        // Colors from variants
        $colors = ProductVariant::whereIn('product_id', (clone $baseQuery)->select('id'))
            ->whereNotNull('color')
            ->select('color', DB::raw('count(DISTINCT product_id) as count'))
            ->groupBy('color')
            ->orderByDesc('count')
            ->get();

        // Sizes from variants
        $sizes = ProductVariant::whereIn('product_id', (clone $baseQuery)->select('id'))
            ->whereNotNull('size')
            ->select('size', DB::raw('count(DISTINCT product_id) as count'))
            ->groupBy('size')
            ->orderByDesc('count')
            ->get();

        return [
            'categories' => $categories,
            'brands'     => $brands,
            'price_range'=> [
                'min' => $priceRange->min_price ?? 0,
                'max' => $priceRange->max_price ?? 10000,
            ],
            'colors'     => $colors,
            'sizes'      => $sizes,
        ];
    }

    /**
     * Log a search query for analytics.
     */
    public function logSearch(string $query, int $resultsCount, array $filters, ?int $userId, ?string $ip): void
    {
        SearchLog::create([
            'query'         => $query,
            'results_count' => $resultsCount,
            'filters'       => !empty($filters) ? $filters : null,
            'user_id'       => $userId,
            'ip_address'    => $ip,
        ]);
    }

    /**
     * Get top search queries for analytics.
     */
    public function getTopSearches(int $limit = 20): array
    {
        return SearchLog::select('query', DB::raw('count(*) as total'), DB::raw('AVG(results_count) as avg_results'))
            ->groupBy('query')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // --- Private Helpers ---

    private function parseSearchQuery(string $query): array
    {
        $include = [];
        $exclude = [];

        // Extract exact phrases in double quotes
        preg_match_all('/"([^"]+)"/', $query, $exactMatches);
        foreach ($exactMatches[1] as $phrase) {
            $include[] = $phrase;
        }
        $remaining = preg_replace('/"([^"]+)"/', '', $query);

        // Split remaining into words
        $words = preg_split('/\s+/', trim($remaining), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            if (str_starts_with($word, '-') && strlen($word) > 1) {
                $exclude[] = substr($word, 1);
            } else {
                $include[] = $word;
            }
        }

        return ['include' => $include, 'exclude' => $exclude];
    }

    private function getCategoryIdsWithChildren($categoryId): array
    {
        $ids = [(int)$categoryId];
        $children = Category::where('parent_id', $categoryId)->pluck('id')->toArray();
        $ids = array_merge($ids, $children);

        // One more level deep
        if (!empty($children)) {
            $grandchildren = Category::whereIn('parent_id', $children)->pluck('id')->toArray();
            $ids = array_merge($ids, $grandchildren);
        }

        return $ids;
    }
}
