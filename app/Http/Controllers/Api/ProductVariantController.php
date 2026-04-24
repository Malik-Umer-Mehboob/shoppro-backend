<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function index($productId)
    {
        $variants = \App\Models\ProductVariant::where('product_id', $productId)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $variants
        ]);
    }

    public function store(Request $request, $productId)
    {
        $product = \App\Models\Product::findOrFail($productId);

        // Check ownership
        if (!$request->user()->hasRole('admin') && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'variants' => 'required|array',
            'variants.*.size' => 'nullable|string',
            'variants.*.color' => 'nullable|string',
            'variants.*.material' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'required|integer|min:0',
            'variants.*.is_active' => 'boolean',
        ]);

        $createdVariants = [];
        foreach ($validated['variants'] as $variantData) {
            $variantData['product_id'] = $productId;
            $createdVariants[] = \App\Models\ProductVariant::create($variantData);
        }

        return response()->json([
            'success' => true,
            'message' => count($createdVariants) . ' variants added successfully',
            'data' => $createdVariants
        ], 201);
    }

    public function update(Request $request, $productId, $variantId)
    {
        $product = \App\Models\Product::findOrFail($productId);
        $variant = \App\Models\ProductVariant::where('product_id', $productId)->findOrFail($variantId);

        if (!$request->user()->hasRole('admin') && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'size' => 'nullable|string',
            'color' => 'nullable|string',
            'material' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $variant->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Variant updated successfully',
            'data' => $variant
        ]);
    }

    public function destroy(Request $request, $productId, $variantId)
    {
        $product = \App\Models\Product::findOrFail($productId);
        $variant = \App\Models\ProductVariant::where('product_id', $productId)->findOrFail($variantId);

        if (!$request->user()->hasRole('admin') && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $variant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Variant deleted successfully'
        ]);
    }
}
