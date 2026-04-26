<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::withCount('products')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string',
            'address' => 'nullable|string',
            'manager_name' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $warehouse = Warehouse::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse created',
            'data' => $warehouse,
        ]);
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->validate([
            'name' => 'sometimes|string',
            'location' => 'sometimes|string',
            'address' => 'nullable|string',
            'manager_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Warehouse updated',
            'data' => $warehouse,
        ]);
    }

    public function destroy($id)
    {
        Warehouse::findOrFail($id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted',
        ]);
    }

    // Get stock per warehouse
    public function stock($warehouseId)
    {
        $warehouse = Warehouse::with(['products' => function ($q) {
            $q->select('products.id', 'name', 'sku', 'thumbnail')
              ->withPivot('quantity', 'reserved_quantity');
        }])->findOrFail($warehouseId);

        return response()->json([
            'success' => true,
            'data' => $warehouse,
        ]);
    }

    // Update product stock in warehouse
    public function updateStock(Request $request, $warehouseId, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $warehouse = Warehouse::findOrFail($warehouseId);

        $warehouse->products()->syncWithoutDetaching([
            $productId => ['quantity' => $request->quantity]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock updated',
        ]);
    }
}
