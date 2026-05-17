<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    protected $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user() ?: auth('sanctum')->user();
        $filters = $request->only(['search', 'status', 'moderation_status', 'category_id', 'per_page', 'page']);
        
        $products = $this->service->listProducts($filters, $user);
        $stats = $this->service->getProductStats($user);

        return $this->success([
            'products' => $products,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $product = $this->service->createProduct($request->validated(), $request->user());

        return $this->success($product, 'Product created successfully', 201);
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

        return $this->success(new ProductResource($product));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, $id)
    {
        try {
            $product = $this->service->updateProduct($id, $request->validated(), $request->user());
            return $this->success($product, 'Product updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->service->deleteProduct($id, $request->user());
            return $this->success(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Upload multiple images for a product.
     */
    public function uploadImages(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if (!$request->user()->isAdmin() && $product->seller_id !== $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            
            if (count($images) > 5) {
                return $this->error('Max 5 images allowed', 422);
            }

            foreach ($images as $index => $image) {
                $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $path = 'products/gallery/' . $filename;
                
                $img = Image::read($image->getRealPath());
                $img->cover(1200, 1200);
                
                Storage::disk('public')->put($path, (string) $img->encodeByExtension($image->getClientOriginalExtension()));

                $hasPrimary = $product->images()->where('is_primary', true)->exists();

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'order' => $index,
                    'is_primary' => !$hasPrimary && $index === 0
                ]);
            }
        }

        return $this->success($product->load('images'), 'Images uploaded successfully');
    }

    /**
     * Delete a product image.
     */
    public function deleteImage(Request $request, $id, $imageId)
    {
        $product = Product::findOrFail($id);

        if (!$request->user()->isAdmin() && $product->seller_id !== $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }

        $image = ProductImage::where('product_id', $id)->findOrFail($imageId);
        
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return $this->success(null, 'Image deleted successfully');
    }

    /**
     * Update product status.
     */
    public function updateStatus(Request $request, $id)
    {
        $user = auth()->user();
        $product = Product::findOrFail($id);

        if ($user->hasRole('seller') && $product->seller_id !== $user->id) {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'status' => 'required|in:draft,published,archived',
        ]);

        $product->update(['status' => $request->status]);
        
        Cache::flush();
        Cache::forget("product_show_{$id}");
        
        return $this->success($product, "Product status updated to {$request->status}");
    }

    public function updateModerationStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'moderation_status' => 'required|in:pending,approved,rejected',
        ]);

        $product = Product::findOrFail($id);
        $product->update(['moderation_status' => $request->moderation_status]);

        // Standardize: If approved, ensure it's at least published if it was draft
        // (Optional based on business logic, but common fix for "pending" confusion)
        if ($request->moderation_status === 'approved' && $product->status === 'draft') {
            $product->update(['status' => 'published']);
        }

        Cache::flush();
        Cache::forget("product_show_{$id}");

        $seller = User::find($product->seller_id);
        if ($seller) {
            $template = $request->moderation_status === 'approved' ? 'product_approved_email' : 'product_rejected_email';
            app(\App\Services\MailService::class)->sendTemplate(
                $template,
                $seller->email,
                $seller->name,
                [
                    'name' => $seller->name,
                    'product_name' => $product->name,
                ]
            );
        }

        return $this->success($product, "Product moderation status updated to {$request->moderation_status}");
    }

    /**
     * Get low stock products (Admin only).
     */
    public function getLowStockProducts()
    {
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
                    'thumbnail' => $this->formatThumbnail($p->thumbnail),
                    'price' => $p->price,
                ];
            });

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
                    'thumbnail' => $this->formatThumbnail($p->thumbnail),
                    'price' => $p->price,
                ];
            });

        $allLowStock = collect()->merge($outOfStock)->merge($belowThreshold);

        return $this->success([
            'products' => $allLowStock,
            'stats' => [
                'out_of_stock' => $outOfStock->count(),
                'below_threshold' => $belowThreshold->count(),
                'total' => $allLowStock->count(),
            ],
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

        return $this->success([
            'id' => $product->id,
            'stock_quantity' => $product->stock_quantity,
        ], "Stock updated. New quantity: {$product->stock_quantity}");
    }

    protected function formatThumbnail($thumbnail)
    {
        if (!$thumbnail) return null;
        if (str_starts_with(trim($thumbnail), 'http')) return trim($thumbnail);
        return asset('storage/' . trim($thumbnail));
    }
}
