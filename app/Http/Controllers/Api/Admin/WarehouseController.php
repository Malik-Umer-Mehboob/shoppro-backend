<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    // Get warehouse with all products assigned to it
    public function stock($warehouseId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);

        $assignedProducts = DB::table('product_warehouse')
            ->join('products', 'products.id', '=', 'product_warehouse.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('product_warehouse.warehouse_id', $warehouseId)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.thumbnail',
                'categories.name as category_name',
                'product_warehouse.quantity',
                'product_warehouse.reserved_quantity'
            )
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'thumbnail' => $p->thumbnail
                        ? asset('storage/' . $p->thumbnail)
                        : null,
                    'category' => $p->category_name ?? 'Uncategorized',
                    'quantity' => $p->quantity,
                    'reserved_quantity' => $p->reserved_quantity,
                    'available' => $p->quantity - $p->reserved_quantity,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'warehouse' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'location' => $warehouse->location,
                ],
                'products' => $assignedProducts,
            ]
        ]);
    }

    // Assign product to warehouse OR update existing stock
    public function updateStock(Request $request, $warehouseId, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        DB::table('product_warehouse')->updateOrInsert(
            [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
            ],
            [
                'quantity' => $request->quantity,
                'reserved_quantity' => $request->reserved_quantity ?? 0,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
        ]);
    }

    // Remove product from warehouse
    public function removeProduct($warehouseId, $productId)
    {
        DB::table('product_warehouse')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from warehouse',
        ]);
    }

    // Get all products NOT yet assigned to this warehouse
    public function availableProducts($warehouseId)
    {
        $assignedIds = DB::table('product_warehouse')
            ->where('warehouse_id', $warehouseId)
            ->pluck('product_id');

        $products = Product::withoutGlobalScopes()
            ->whereNotIn('id', $assignedIds)
            ->where('status', 'published')
            ->select('id', 'name', 'sku', 'stock_quantity', 'thumbnail')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock_quantity' => $p->stock_quantity,
                    'thumbnail' => $p->thumbnail
                        ? asset('storage/' . $p->thumbnail)
                        : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
