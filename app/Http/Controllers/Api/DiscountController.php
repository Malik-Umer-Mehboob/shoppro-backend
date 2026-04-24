<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = \App\Models\Discount::with(['product', 'category'])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $discounts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'badge_text' => 'nullable|string|max:50',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $discount = \App\Models\Discount::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Discount created successfully',
            'data' => $discount
        ], 201);
    }

    public function show($id)
    {
        $discount = \App\Models\Discount::with(['product', 'category'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $discount
        ]);
    }

    public function update(Request $request, $id)
    {
        $discount = \App\Models\Discount::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'sometimes|required|in:percentage,fixed',
            'value' => 'sometimes|required|numeric|min:0',
            'badge_text' => 'nullable|string|max:50',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $discount->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Discount updated successfully',
            'data' => $discount
        ]);
    }

    public function destroy($id)
    {
        $discount = \App\Models\Discount::findOrFail($id);
        $discount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Discount deleted successfully'
        ]);
    }
}
