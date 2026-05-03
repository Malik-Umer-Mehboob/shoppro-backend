<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LocalizationController extends Controller
{
    /**
     * Get active languages with caching.
     */
    public function languages()
    {
        try {
            $languages = Cache::remember('languages', 3600, function () {
                return DB::table('languages')
                    ->where('is_active', true)
                    ->select('id', 'name', 'code')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => $languages
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Localization Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
