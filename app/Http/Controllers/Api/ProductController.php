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

        // Optimized Stats calculation in a single query
        $statsQuery = \Illuminate\Support\Facades\DB::table('products');
        if ($user && $user->hasRole('seller')) {
            $statsQuery->where('seller_id', $user->id);
        }

        $stats = (array) $statsQuery->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
            SUM(CASE WHEN stock_quantity <= low_stock_threshold AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock
        ")->first();

        $cacheKey = "products_index_{$userRole}_{$userId}_p{$page}_s{$search}_c{$categoryId}_min{$minPrice}_max{$maxPrice}_st{$status}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $user, $stats) {
            if ($user && $user->hasRole('admin')) {
                $query = Product::select(
                    'id', 'name', 'slug', 'price', 'sale_price', 
                    'thumbnail', 'category_id', 'seller_id', 
                    'stock_quantity', 'status', 'is_featured', 
                    'sku', 'created_at'
                )->with([
                    'category:id,name,slug',
                    'images:id,product_id,image_path,is_primary',
                    'seller:id,name'
                ]);
            } elseif ($user && $user->hasRole('seller')) {
                $query = Product::select(
                    'id', 'name', 'slug', 'price', 'sale_price', 
                    'thumbnail', 'category_id', 'seller_id', 
                    'stock_quantity', 'status', 'is_featured', 
                    'sku', 'created_at'
                )->with([
                    'category:id,name,slug',
                    'images:id,product_id,image_path,is_primary',
                    'seller:id,name'
                ])->where('seller_id', $user->id);
            } else {
                $query = Product::select(
                    'id', 'name', 'slug', 'price', 'sale_price', 
                    'thumbnail', 'category_id', 'seller_id', 
                    'stock_quantity', 'status', 'is_featured', 
                    'sku', 'created_at'
                )->with([
                    'category:id,name,slug',
                    'images:id,product_id,image_path,is_primary',
                    'seller:id,name'
                ])->where('status', 'published');
            }

            // Apply filters
            if ($request->filled('category_id') && $request->category_id !== 'all') {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Allow Admin/Seller to filter by specific status
            if ($request->filled('status') && $request->status !== 'all' && ($user && ($user->hasRole('admin') || $user->hasRole('seller')))) {
                $query->where('status', $request->status);
            }

            $products = $query->latest()->paginate(12);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products,
                    'stats' => $stats
                ]
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

        // Low stock / out-of-stock notifications
        $product->refresh();
        if ($product->stock_quantity === 0) {
            \App\Helpers\NotificationHelper::sendToRole(
                'admin',
                'stock.out',
                'Out of Stock! 🚫',
                "'{$product->name}' is now out of stock!",
                ['url' => '/admin/low-stock']
            );
            if ($product->seller_id) {
                \App\Helpers\NotificationHelper::send(
                    $product->seller_id,
                    'stock.out',
                    'Out of Stock! 🚫',
                    "Your product '{$product->name}' is now out of stock!",
                    ['url' => '/seller/products']
                );
            }
        } elseif ($product->stock_quantity <= $product->low_stock_threshold && $product->stock_quantity > 0) {
            \App\Helpers\NotificationHelper::sendToRole(
                'admin',
                'stock.low',
                'Low Stock Alert! ⚠️',
                "'{$product->name}' has only {$product->stock_quantity} units left.",
                ['url' => '/admin/low-stock']
            );
            if ($product->seller_id) {
                \App\Helpers\NotificationHelper::send(
                    $product->seller_id,
                    'stock.low',
                    'Low Stock Alert! ⚠️',
                    "Your product '{$product->name}' has only {$product->stock_quantity} units left.",
                    ['url' => '/seller/products']
                );
            }
        }

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
        // Out of stock products (stock = 0)
        $outOfStock = Product::withoutGlobalScopes()
            ->where('stock_quantity', 0)
            ->with(['category'])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock_quantity' => $p->stock_quantity,
                    'low_stock_threshold' => $p->low_stock_threshold,
                    'status' => 'out_of_stock',
                    'category' => $p->category?->name ?? 'Uncategorized',
                    'thumbnail' => $p->thumbnail
                        ? asset('storage/' . $p->thumbnail)
                        : null,
                    'price' => $p->price,
                ];
            });

        // Below threshold products (stock > 0 but <= threshold)
        $belowThreshold = Product::withoutGlobalScopes()
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->with(['category'])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock_quantity' => $p->stock_quantity,
                    'low_stock_threshold' => $p->low_stock_threshold,
                    'status' => 'low_stock',
                    'category' => $p->category?->name ?? 'Uncategorized',
                    'thumbnail' => $p->thumbnail
                        ? asset('storage/' . $p->thumbnail)
                        : null,
                    'price' => $p->price,
                ];
            });

        $allLowStock = collect()
            ->merge($outOfStock)
            ->merge($belowThreshold);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $allLowStock,
                'stats' => [
                    'out_of_stock' => $outOfStock->count(),
                    'below_threshold' => $belowThreshold->count(),
                    'total' => $allLowStock->count(),
                ],
            ]
        ]);
    }

    /**
     * Restock a product.
     */
    public function restock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::withoutGlobalScopes()->findOrFail($id);
        $product->increment('stock_quantity', $request->quantity);

        return response()->json([
            'success' => true,
            'message' => "Stock updated. New quantity: {$product->stock_quantity}",
            'data' => [
                'id' => $product->id,
                'stock_quantity' => $product->stock_quantity,
            ]
        ]);
    }
}
