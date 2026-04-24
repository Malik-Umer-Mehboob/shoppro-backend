<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Use the sanctum guard explicitly so the user is identified even if 
        // the middleware is not applied to the route (optional authentication).
        $user = auth('sanctum')->user();

        if ($user && $user->hasRole('admin')) {
            $query = Product::with(['category', 'images', 'seller']);
        } elseif ($user && $user->hasRole('seller')) {
            $query = Product::with(['category', 'images', 'seller'])
                ->where('seller_id', $user->id);
        } else {
            $query = Product::with(['category', 'images', 'seller'])
                ->where('status', 'published');
        }

        // Apply filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Allow Admin/Seller to filter by specific status
        if ($request->has('status') && ($user && ($user->hasRole('admin') || $user->hasRole('seller')))) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        // Convert is_featured to boolean explicitly
        $validated['is_featured'] = filter_var(
            $request->input('is_featured', false), 
            FILTER_VALIDATE_BOOLEAN
        );

        // Set defaults
        $validated['seller_id'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'draft';

        if ($request->hasFile('thumbnail')) {
            $image = $request->file('thumbnail');
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $path = 'products/' . $filename;
            
            // Resize using Intervention Image
            $img = Image::read($image->getRealPath());
            $img->cover(800, 800);
            
            Storage::disk('public')->put($path, (string) $img->encodeByExtension($image->getClientOriginalExtension()));
            $validated['thumbnail'] = $path;
        }

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['images', 'category', 'seller:id,name'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Check ownership
        if (!$request->user()->isAdmin() && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        if ($request->has('is_featured')) {
            $validated['is_featured'] = filter_var(
                $request->input('is_featured', false),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            $image = $request->file('thumbnail');
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $path = 'products/' . $filename;
            
            $img = Image::read($image->getRealPath());
            $img->cover(800, 800);
            
            Storage::disk('public')->put($path, (string) $img->encodeByExtension($image->getClientOriginalExtension()));
            $validated['thumbnail'] = $path;
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Check ownership
        if (!$request->user()->isAdmin() && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Upload multiple images for a product.
     */
    public function uploadImages(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if (!$request->user()->isAdmin() && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            
            if (count($images) > 5) {
                return response()->json(['message' => 'Max 5 images allowed'], 422);
            }

            foreach ($images as $index => $image) {
                $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $path = 'products/gallery/' . $filename;
                
                $img = Image::read($image->getRealPath());
                $img->cover(1200, 1200);
                
                Storage::disk('public')->put($path, (string) $img->encodeByExtension($image->getClientOriginalExtension()));

                // Check if primary exists
                $hasPrimary = $product->images()->where('is_primary', true)->exists();

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'order' => $index,
                    'is_primary' => !$hasPrimary && $index === 0
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => $product->load('images')
        ]);
    }

    /**
     * Delete a product image.
     */
    public function deleteImage(Request $request, $id, $imageId)
    {
        $product = Product::findOrFail($id);

        if (!$request->user()->isAdmin() && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $image = ProductImage::where('product_id', $id)->findOrFail($imageId);
        
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }

    /**
     * Update product status.
     */
    public function updateStatus(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if (!$request->user()->isAdmin() && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:draft,published,archived'
        ]);

        $product->status = $request->status;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Get low stock products (Admin only).
     */
    public function getLowStockProducts()
    {
        $products = Product::whereRaw('stock_quantity <= low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->with(['category', 'seller:id,name'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}
