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
    /**
     * Export search analytics data as CSV.
     */
    public function export()
    {
        try {
            $filename = "search_analytics_" . date('Y-m-d') . ".csv";
            
            $handle = fopen('php://temp', 'w+');

            // 1. Summary Stats
            fputcsv($handle, ['SEARCH ANALYTICS SUMMARY']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Today\'s Searches', SearchLog::whereDate('created_at', today())->count()]);
            fputcsv($handle, ['This Month\'s Searches', SearchLog::whereMonth('created_at', now()->month)->count()]);
            fputcsv($handle, ['Unique Queries (Month)', SearchLog::whereMonth('created_at', now()->month)->distinct('query')->count('query')]);
            fputcsv($handle, ['Zero Result Queries (30d)', SearchLog::where('results_count', 0)->where('created_at', '>=', now()->subDays(30))->count()]);
            fputcsv($handle, []); // Spacer

            // 2. Top Keywords
            fputcsv($handle, ['TOP KEYWORDS (LAST 30 DAYS)']);
            fputcsv($handle, ['Rank', 'Keyword', 'Searches', 'Avg Results']);
            
            $topKeywords = SearchLog::select('query', 
                    DB::raw('COUNT(*) as search_count'),
                    DB::raw('AVG(results_count) as avg_results')
                )
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('query')
                ->orderByDesc('search_count')
                ->take(50)
                ->get();

            foreach ($topKeywords as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row->query,
                    $row->search_count,
                    round($row->avg_results, 2)
                ]);
            }
            fputcsv($handle, []); // Spacer

            // 3. Zero Results
            fputcsv($handle, ['ZERO RESULT QUERIES (LAST 30 DAYS)']);
            fputcsv($handle, ['Keyword', 'Times Searched']);

            $noResults = SearchLog::select('query',
                    DB::raw('COUNT(*) as search_count')
                )
                ->where('results_count', 0)
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('query')
                ->orderByDesc('search_count')
                ->take(50)
                ->get();

            foreach ($noResults as $row) {
                fputcsv($handle, [
                    $row->query,
                    $row->search_count
                ]);
            }

            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);

            return response($csvContent, 200, [
                "Content-Type" => "text/csv",
                "Content-Disposition" => "attachment; filename=$filename",
                "Cache-Control" => "no-cache, no-store, must-revalidate",
                "Pragma" => "no-cache",
                "Expires" => "0"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
