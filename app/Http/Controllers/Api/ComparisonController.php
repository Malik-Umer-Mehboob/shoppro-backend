<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ComparisonController extends Controller
{
    /**
     * Get the products in the comparison list.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) return response()->json(['success' => true, 'data' => []]);

            $comparison = \App\Models\ProductComparison::where('user_id', $user->id)->first();
            if (!$comparison) return response()->json(['success' => true, 'data' => []]);

            $items = \App\Models\ProductComparisonItem::where('comparison_id', $comparison->id)
                ->with(['product:id,name,price,sale_price,thumbnail,category_id,stock_quantity,status,sku', 'product.category:id,name'])
                ->limit(4)
                ->get();

            $products = $items->map(fn($item) => $item->product)->filter()->values();

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => true, 'data' => []]);
        }
    }

    /**
     * Add a product to the comparison list.
     */
    public function store(Request $request)
    {
        try {
            $request->validate(['product_id' => 'required|exists:products,id']);
            
            $comparison = session('comparison', []);
            
            if (count($comparison) >= 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum 4 products can be compared',
                ], 422);
            }
            
            if (!in_array($request->product_id, $comparison)) {
                $comparison[] = $request->product_id;
                session(['comparison' => $comparison]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product added to comparison',
                'data' => ['count' => count($comparison)],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add to comparison',
            ], 500);
        }
    }

    /**
     * Remove a product from the comparison list.
     */
    public function destroy(Request $request, $productId)
    {
        try {
            $comparison = session('comparison', []);
            $comparison = array_filter($comparison, fn($id) => $id != $productId);
            session(['comparison' => array_values($comparison)]);

            return response()->json([
                'success' => true,
                'message' => 'Product removed from comparison',
                'data' => ['count' => count($comparison)],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove from comparison',
            ], 500);
        }
    }

    /**
     * Clear the comparison list.
     */
    public function clear(Request $request)
    {
        try {
            session()->forget('comparison');
            return response()->json([
                'success' => true,
                'message' => 'Comparison cleared',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear comparison',
            ], 500);
        }
    }
}
