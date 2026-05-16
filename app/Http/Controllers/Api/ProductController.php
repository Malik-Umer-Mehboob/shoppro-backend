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
        $user = $request->user() ?: auth('sanctum')->user();
        $isSeller = $user && $user->hasRole('seller');
        $isAdmin = $user && $user->hasRole('admin');

        $query = Product::select(
            'id', 'name', 'slug', 'price', 'sale_price',
            'thumbnail', 'category_id', 'seller_id',
            'stock_quantity', 'status', 'is_featured',
            'sku', 'short_description', 'created_at'
        )->with([
            'category:id,name,slug,parent_id',
        ]);

        if ($isAdmin) {
            $query = Product::query()
                ->select(
                    'id', 'name', 'slug', 'price', 'sale_price',
                    'thumbnail', 'category_id', 'seller_id',
                    'stock_quantity', 'status', 'is_featured',
                    'sku', 'short_description', 'created_at'
                )->with(['category:id,name,slug', 'seller:id,name']);
        } elseif ($isSeller) {
            $query->where('seller_id', $user->id);
        } else {
            $query->where('status', 'published')
                  ->where('moderation_status', 'approved');
        }

        if ($request->search) {
            $query->where('name', 'like',
                '%' . $request->search . '%');
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->moderation_status) {
            $query->where('moderation_status', $request->moderation_status);
        }

        if ($request->category_id) {
            $query->where('category_id',
                $request->category_id);
        }

        $products = $query->latest()->paginate(12);

        // Stats with single optimized query
        $statsQuery = $isAdmin
            ? Product::query()
            : ($isSeller
                ? Product::where('seller_id', $user->id)
                : Product::where('status', 'published')->where('moderation_status', 'approved'));

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'published' => (clone $statsQuery)
                ->where('status', 'published')->count(),
            'draft' => (clone $statsQuery)
                ->where('status', 'draft')->count(),
            'archived' => (clone $statsQuery)
                ->where('status', 'archived')->count(),
            'approved' => (clone $statsQuery)
                ->where('moderation_status', 'approved')->count(),
            'pending' => (clone $statsQuery)
                ->where('moderation_status', 'pending')->count(),
            'rejected' => (clone $statsQuery)
                ->where('moderation_status', 'rejected')->count(),
            'low_stock' => (clone $statsQuery)
                ->whereColumn('stock_quantity', '<=',
                    'low_stock_threshold')
                ->where('stock_quantity', '>', 0)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'stats' => $stats,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string',
            'short_description'  => 'nullable|string|max:500',
            'price'              => 'required|numeric|min:0',
            'sale_price'         => 'nullable|numeric|min:0',
            'category_id'        => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($request) {
                    $user = $request->user();
                    if ($user && $user->hasRole('seller')) {
                        $isAssigned = $user->assignedCategories()->where('categories.id', $value)->exists();
                        if (!$isAssigned) {
                            $fail('You are not authorized to upload products in this category.');
                        }
                    }
                },
            ],
            'stock_quantity'     => 'required|integer|min:0',
            'low_stock_threshold'=> [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    $stock = (int) $request->input('stock_quantity', 0);
                    if ($value !== null && $value >= $stock) {
                        $fail('Low stock threshold must be less than stock quantity.');
                    }
                },
            ],
            'status'             => 'nullable|in:draft,published,archived',
            'is_featured'        => 'nullable|boolean',
            'thumbnail'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'tags'               => 'nullable|array',
            'tags.*'             => 'string',
        ]);

        // Convert is_featured to boolean explicitly
        $validated['is_featured'] = filter_var(
            $request->input('is_featured', false), 
            FILTER_VALIDATE_BOOLEAN
        );

        // Set defaults
        $validated['seller_id'] = auth()->id();
        $validated['status'] = $request->input('status', 'draft');
        $validated['moderation_status'] = 'pending';

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
            ->where('status', 'approved')->avg('rating');
        $reviewCount = $product->reviews()
            ->where('status', 'approved')->count();

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
                'low_stock_threshold' => $product->low_stock_threshold,
                'status' => $product->status,
                'is_featured' => $product->is_featured,
                'category_id' => $product->category_id,
                'thumbnail' => $product->thumbnail
                    ? (str_starts_with(trim($product->thumbnail), 'http')
                        ? trim($product->thumbnail)
                        : asset('storage/' . trim($product->thumbnail)))
                    : null,
                'category' => $product->category?->name,
                'seller' => $product->seller?->name,
                'images' => $product->images->map(fn($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/' . $img->image_path),
                    'is_primary' => $img->is_primary,
                ]),
                'variants' => $product->variants,
                'tags' => $product->tags ?? [],
                'average_rating' => round($avgRating ?? 0, 1),
                'total_reviews' => $reviewCount,
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
            'name'               => 'sometimes|required|string|max:255',
            'description'        => 'nullable|string',
            'short_description'  => 'nullable|string|max:500',
            'price'              => 'sometimes|required|numeric|min:0',
            'sale_price'         => 'nullable|numeric|min:0',
            'category_id'        => [
                'sometimes',
                'required',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($request) {
                    $user = $request->user();
                    if ($user && $user->hasRole('seller')) {
                        $isAssigned = $user->assignedCategories()->where('categories.id', $value)->exists();
                        if (!$isAssigned) {
                            $fail('You are not authorized to upload products in this category.');
                        }
                    }
                },
            ],
            'stock_quantity'     => 'sometimes|required|integer|min:0',
            'low_stock_threshold'=> [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $product) {
                    $stock = $request->has('stock_quantity')
                        ? (int) $request->input('stock_quantity')
                        : $product->stock_quantity;
                    if ($value !== null && $value >= $stock) {
                        $fail('Low stock threshold must be less than stock quantity.');
                    }
                },
            ],
            'status'             => 'nullable|in:draft,published,archived',
            'is_featured'        => 'nullable|boolean',
            'thumbnail'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'tags'               => 'nullable|array',
            'tags.*'             => 'string',
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

        // Check ownership/permissions
        $user = $request->user();
        if (!$user->hasRole('admin') && $product->seller_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
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
            'data' => $product
        ]);
    }

    public function updateModerationStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'moderation_status' => 'required|in:pending,approved,rejected',
        ]);

        $product = Product::findOrFail($id);
        $product->update(['moderation_status' => $request->moderation_status]);

        return response()->json([
            'success' => true,
            'message' => "Product moderation status updated to {$request->moderation_status}",
            'data' => $product
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
                        ? (str_starts_with(trim($p->thumbnail), 'http')
                            ? trim($p->thumbnail)
                            : asset('storage/' . trim($p->thumbnail)))
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
                        ? (str_starts_with(trim($p->thumbnail), 'http')
                            ? trim($p->thumbnail)
                            : asset('storage/' . trim($p->thumbnail)))
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
