<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchAnalyticsController extends Controller
{
    /**
     * Get search analytics data.
     */
    public function index()
    {
        try {
            // Top searched keywords (last 30 days)
            $topKeywords = SearchLog::select('query', 
                    DB::raw('COUNT(*) as search_count'),
                    DB::raw('AVG(results_count) as avg_results')
                )
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('query')
                ->orderByDesc('search_count')
                ->take(20)
                ->get();

            // Zero results searches (last 30 days)
            $noResults = SearchLog::select('query',
                    DB::raw('COUNT(*) as search_count')
                )
                ->where('results_count', 0)
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('query')
                ->orderByDesc('search_count')
                ->take(10)
                ->get();

            // Total searches today
            $todaySearches = SearchLog::whereDate('created_at', today())->count();

            // Total searches this month
            $monthSearches = SearchLog::whereMonth('created_at', now()->month)->count();

            // Total unique queries this month
            $uniqueQueries = SearchLog::whereMonth('created_at', now()->month)
                ->distinct('query')
                ->count('query');

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'today_searches' => $todaySearches,
                        'month_searches' => $monthSearches,
                        'unique_queries' => $uniqueQueries,
                        'no_results_count' => SearchLog::where('results_count', 0)->where('created_at', '>=', now()->subDays(30))->count(),
                    ],
                    'top_keywords' => $topKeywords,
                    'no_results' => $noResults,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load search analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
