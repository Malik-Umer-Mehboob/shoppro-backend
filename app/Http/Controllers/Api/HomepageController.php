<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomepageController extends Controller
{
    /**
     * Get optimized homepage data in a single request.
     */
    public function index(Request $request)
    {
        // Cache homepage data for 1 hour to reduce DB load
        $data = Cache::remember('homepage_data', 3600, function () {
            
            // Optimize query: only select needed columns and eager load specific relations
            $featuredProducts = Product::select(
                'id', 'name', 'slug', 'price', 'sale_price',
                'thumbnail', 'category_id', 'status', 'moderation_status', 'is_featured'
            )
            ->with(['category:id,name,slug'])
            ->where('status', 'published')
            ->where('moderation_status', 'approved')
            // Optionally, add where('is_featured', true) if needed, 
            // but the original frontend just fetched latest 8 published products.
            ->latest()
            ->take(8)
            ->get();

            return [
                'featured_products' => $featuredProducts,
                // Add more keys here in the future without breaking frontend
                // 'categories' => ...,
                // 'banners' => ...
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
