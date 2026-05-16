<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BulkUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));

        if (count($data) < 2) {
            return response()->json(['message' => 'The CSV file is empty or missing data.'], 422);
        }

        $header = array_shift($data);
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $successCount = 0;
        $errors = [];
        $user = auth()->user();
        $isSeller = $user->hasRole('seller');

        // Pre-fetch categories: if seller, only assigned ones
        $categoriesQuery = \App\Models\Category::query();
        if ($isSeller) {
            $categoriesQuery = $user->assignedCategories();
        }
        $categories = $categoriesQuery->get(['categories.id', 'categories.name'])->pluck('id', 'name')->toArray();

        foreach ($data as $index => $row) {
            $row = array_map('trim', $row);
            if (count($row) < count($header)) {
                $errors[] = "Row " . ($index + 2) . ": Column count mismatch.";
                continue;
            }

            $item = array_combine(array_slice($header, 0, count($row)), $row);
            
            try {
                if (empty($item['name']) || !isset($item['price'])) {
                    throw new \Exception("Name and Price are required.");
                }

                // Resolve Category ID
                $categoryId = null;
                if (!empty($item['category'])) {
                    $catName = trim($item['category']);
                    // Try exact match
                    $categoryId = $categories[$catName] ?? null;
                    // If not found, try case-insensitive
                    if (!$categoryId) {
                        foreach ($categories as $name => $id) {
                            if (strtolower($name) === strtolower($catName)) {
                                $categoryId = $id;
                                break;
                            }
                        }
                    }
                } elseif (!empty($item['category_id'])) {
                    $candidateId = (int)$item['category_id'];
                    // If seller, check if this ID is in their assigned list
                    if ($isSeller) {
                        if (in_array($candidateId, $categories)) {
                            $categoryId = $candidateId;
                        }
                    } else {
                        $categoryId = $candidateId;
                    }
                }

                if (!$categoryId) {
                    throw new \Exception("Invalid or unauthorized category.");
                }

                // Create Product
                $product = \App\Models\Product::create([
                    'seller_id' => $user->id,
                    'category_id' => $categoryId,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? ($item['desc'] ?? null),
                    'price' => (float)$item['price'],
                    'sale_price' => isset($item['sale_price']) ? (float)$item['sale_price'] : null,
                    'stock_quantity' => isset($item['stock']) ? (int)$item['stock'] : (isset($item['stock_quantity']) ? (int)$item['stock_quantity'] : 0),
                    'low_stock_threshold' => (int)($item['low_stock_threshold'] ?? 5),
                    'thumbnail' => $item['image'] ?? ($item['thumbnail'] ?? null),
                    'status' => $item['status'] ?? 'draft',
                    'moderation_status' => 'pending',
                ]);

                // Create Default Variant
                \App\Models\ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                    'material' => $item['material'] ?? null,
                    'price' => $product->price,
                    'stock_quantity' => $product->stock_quantity,
                    'is_active' => true,
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "$successCount products imported successfully.",
            'errors' => $errors
        ]);
    }
}
