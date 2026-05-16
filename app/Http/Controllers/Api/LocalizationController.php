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
        $languages = \Cache::remember(
            'languages_all', 3600, // 1 hour
            fn() => \DB::table('languages')
                ->where('is_active', true)
                ->select('id', 'name', 'code', 'is_default')
                ->get()
        );

        return response()->json([
            'success' => true,
            'data' => $languages,
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
