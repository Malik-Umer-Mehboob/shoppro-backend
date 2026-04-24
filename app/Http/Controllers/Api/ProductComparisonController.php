<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductComparison;
use App\Models\ProductComparisonItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductComparisonController extends Controller
{
    public function getComparison(Request $request)
    {
        $comparison = $this->getOrCreateComparison($request);
        return response()->json($comparison->load('products.category', 'products.brand'));
    }

    public function addToComparison(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        
        $comparison = $this->getOrCreateComparison($request);
        
        if ($comparison->items()->count() >= 4) {
            return response()->json(['message' => 'You can only compare up to 4 products.'], 422);
        }

        if (!$comparison->items()->where('product_id', $request->product_id)->exists()) {
            $comparison->items()->create(['product_id' => $request->product_id]);
        }

        return response()->json($comparison->load('products'));
    }

    public function removeFromComparison(Request $request, $productId)
    {
        $comparison = $this->getOrCreateComparison($request);
        $comparison->items()->where('product_id', $productId)->delete();

        return response()->json(['message' => 'Product removed from comparison.']);
    }

    public function clearComparison(Request $request)
    {
        $comparison = $this->getOrCreateComparison($request);
        $comparison->items()->delete();

        return response()->json(['message' => 'Comparison cleared.']);
    }

    private function getOrCreateComparison(Request $request)
    {
        $sessionId = $request->cookie('comparison_session_id') ?? Str::random(40);
        
        if ($request->user()) {
            $comparison = ProductComparison::firstOrCreate(['user_id' => $request->user()->id]);
            // Merge session comparison if exists
            $sessionComparison = ProductComparison::where('session_id', $sessionId)->first();
            if ($sessionComparison) {
                foreach ($sessionComparison->items as $item) {
                    if (!$comparison->items()->where('product_id', $item->product_id)->exists()) {
                        $comparison->items()->create(['product_id' => $item->product_id]);
                    }
                }
                $sessionComparison->delete();
            }
        } else {
            $comparison = ProductComparison::firstOrCreate(['session_id' => $sessionId]);
        }

        // Set cookie if not present
        if (!$request->cookie('comparison_session_id')) {
            cookie()->queue('comparison_session_id', $sessionId, 60 * 24 * 30);
        }

        return $comparison;
    }
}
