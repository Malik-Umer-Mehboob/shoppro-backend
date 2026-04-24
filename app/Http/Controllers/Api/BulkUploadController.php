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
        // Clean header
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $successCount = 0;
        $errors = [];
        $sellerId = auth()->id();

        foreach ($data as $index => $row) {
            if (count($row) !== count($header)) {
                $errors[] = "Row " . ($index + 2) . ": Column count mismatch.";
                continue;
            }

            $item = array_combine($header, $row);
            
            try {
                // Basic validation
                if (empty($item['name']) || empty($item['price'])) {
                    throw new \Exception("Name and Price are required.");
                }

                // Create Product
                $product = \App\Models\Product::create([
                    'seller_id' => $sellerId,
                    'category_id' => $item['category_id'] ?: null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?: null,
                    'price' => $item['price'],
                    'sale_price' => $item['sale_price'] ?: null,
                    'stock_quantity' => $item['stock_quantity'] ?: 0,
                    'status' => $item['status'] ?: 'published',
                ]);

                // Create Variant if any variant field is present
                if (!empty($item['size']) || !empty($item['color']) || !empty($item['material'])) {
                    \App\Models\ProductVariant::create([
                        'product_id' => $product->id,
                        'size' => $item['size'] ?: null,
                        'color' => $item['color'] ?: null,
                        'material' => $item['material'] ?: null,
                        'price' => $item['price'], // Default to main price
                        'stock_quantity' => $item['stock_quantity'] ?: 0,
                        'is_active' => true,
                    ]);
                }

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
