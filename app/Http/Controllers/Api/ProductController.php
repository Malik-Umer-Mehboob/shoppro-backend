<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Models\ActivityLog;


class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Generate a unique cache key based on request parameters
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        $categoryId = $request->get('category_id', '');
        $minPrice = $request->get('min_price', '');
        $maxPrice = $request->get('max_price', '');
        $status = $request->get('status', '');
        
        $user = auth('sanctum')->user();
        $userRole = $user ? $user->getRoleNames()->first() : 'guest';
        $userId = $user ? $user->id : 'none';

        $cacheKey = "products_index_{$userRole}_{$userId}_p{$page}_s{$search}_c{$categoryId}_min{$minPrice}_max{$maxPrice}_st{$status}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $user) {
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
        });
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

        ActivityLog::log('product.created',
            "Product '{$product->name}' created",
            'Product', $product->id
        );

        // Invalidate product cache
        Cache::flush();

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
        $product = Product::with([
            'category', 'images', 'seller:id,name',
            'variants', 'reviews'
        ])->findOrFail($id);

        $avgRating = $product->reviews()
            ->where('is_approved', true)->avg('rating');
        $reviewCount = $product->reviews()
            ->where('is_approved', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'sku' => $product->sku,
                'stock_quantity' => $product->stock_quantity,
                'status' => $product->status,
                'thumbnail' => $product->thumbnail
                    ? asset('storage/' . $product->thumbnail)
                    : null,
                'category' => $product->category?->name,
                'seller' => $product->seller?->name,
                'images' => $product->images->map(fn($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/' . $img->image_path),
                    'is_primary' => $img->is_primary,
                ]),
                'variants' => $product->variants,
                'average_rating' => round($avgRating ?? 0, 1),
                'review_count' => $reviewCount,
                // SEO fields
                'seo' => [
                    'title' => $product->name . ' | ShopPro',
                    'description' => $product->short_description
                        ?? substr(strip_tags($product->description ?? ''), 0, 160),
                    'keywords' => implode(', ', array_filter([
                        $product->name,
                        $product->category?->name,
                        $product->sku,
                    ])),
                    'og_image' => $product->thumbnail
                        ? asset('storage/' . $product->thumbnail)
                        : null,
                    'canonical_url' => env('FRONTEND_URL', 'http://localhost:5173')
                        . '/products/' . $product->id,
                    'schema' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Product',
                        'name' => $product->name,
                        'description' => $product->short_description,
                        'sku' => $product->sku,
                        'offers' => [
                            '@type' => 'Offer',
                            'price' => $product->sale_price ?? $product->price,
                            'priceCurrency' => 'PKR',
                            'availability' => $product->stock_quantity > 0
                                ? 'https://schema.org/InStock'
                                : 'https://schema.org/OutOfStock',
                        ],
                        'aggregateRating' => $reviewCount > 0 ? [
                            '@type' => 'AggregateRating',
                            'ratingValue' => round($avgRating, 1),
                            'reviewCount' => $reviewCount,
                        ] : null,
                    ],
                ],
            ]
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

        // Invalidate product cache
        Cache::forget("product_show_{$id}");
        Cache::flush();

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

        ActivityLog::log('product.deleted',
            "Product deleted",
            'Product', $id
        );

        // Invalidate product cache
        Cache::forget("product_show_{$id}");
        Cache::flush();

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
        $user = auth()->user();
        $product = Product::findOrFail($id);

        // Seller can only update own products
        if ($user->hasRole('seller') && $product->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:draft,published,archived',
        ]);

        $product->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "Product status updated to {$request->status}",
            'data' => [
                'id' => $product->id,
                'status' => $product->status,
            ]
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
