<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Models\Product;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Cache;
use App\Helpers\NotificationHelper;

class ProductService extends BaseService
{
    protected $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listProducts(array $filters, $user = null)
    {
        $userId = $user ? $user->id : 'guest';
        $filterHash = md5(json_encode($filters));
        $cacheKey = "products_list_{$userId}_{$filterHash}";

        return Cache::remember($cacheKey, 300, function () use ($filters, $user) {
            return $this->repository->getFilteredProducts($filters, $user);
        });
    }

    public function getProductStats($user = null)
    {
        $userId = $user ? $user->id : 'guest';
        $cacheKey = "products_stats_{$userId}";

        return Cache::remember($cacheKey, 600, function () use ($user) {
            return $this->repository->getProductStats($user);
        });
    }

    public function createProduct(array $data, $user)
    {
        return $this->handleTransaction(function () use ($data, $user) {
            $data['seller_id'] = $user->id;
            $data['status'] = $data['status'] ?? 'draft';
            $data['moderation_status'] = 'pending';

            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof \Illuminate\Http\UploadedFile) {
                $data['thumbnail'] = $this->uploadImage($data['thumbnail'], 'products');
            }

            $product = $this->repository->create($data);

            ActivityLog::log('product.created', "Product '{$product->name}' created", 'Product', $product->id);
            Cache::flush();

            return $product;
        }, 'Failed to create product');
    }

    public function updateProduct($id, array $data, $user)
    {
        return $this->handleTransaction(function () use ($id, $data, $user) {
            $product = $this->repository->findOrFail($id);

            // Authorization check (could also be moved to a Policy)
            if (!$user->hasRole('admin') && $product->seller_id !== $user->id) {
                throw new \Exception('Unauthorized', 403);
            }

            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof \Illuminate\Http\UploadedFile) {
                // Delete old thumbnail
                if ($product->thumbnail) {
                    Storage::disk('public')->delete($product->thumbnail);
                }
                $data['thumbnail'] = $this->uploadImage($data['thumbnail'], 'products');
            }

            $this->repository->update($id, $data);
            $product->refresh();

            $this->checkStockLevels($product);

            Cache::forget("product_show_{$id}");
            Cache::flush();

            return $product;
        }, 'Failed to update product');
    }

    public function deleteProduct($id, $user)
    {
        return $this->handleTransaction(function () use ($id, $user) {
            $product = $this->repository->findOrFail($id);

            if (!$user->hasRole('admin') && $product->seller_id !== $user->id) {
                throw new \Exception('Unauthorized', 403);
            }

            $this->repository->delete($id);

            ActivityLog::log('product.deleted', "Product deleted", 'Product', $id);
            Cache::forget("product_show_{$id}");
            Cache::flush();

            return true;
        }, 'Failed to delete product');
    }

    protected function uploadImage($image, $directory)
    {
        $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
        $path = $directory . '/' . $filename;
        
        $img = Image::read($image->getRealPath());
        $img->cover(800, 800);
        
        Storage::disk('public')->put($path, (string) $img->encodeByExtension($image->getClientOriginalExtension()));
        
        return $path;
    }

    protected function checkStockLevels(Product $product)
    {
        if ($product->stock_quantity === 0) {
            NotificationHelper::sendToRole('admin', 'stock.out', 'Out of Stock! 🚫', "'{$product->name}' is now out of stock!", ['url' => '/admin/low-stock']);
            if ($product->seller_id) {
                NotificationHelper::send($product->seller_id, 'stock.out', 'Out of Stock! 🚫', "Your product '{$product->name}' is now out of stock!", ['url' => '/seller/products']);
            }
        } elseif ($product->stock_quantity <= $product->low_stock_threshold && $product->stock_quantity > 0) {
            NotificationHelper::sendToRole('admin', 'stock.low', 'Low Stock Alert! ⚠️', "'{$product->name}' has only {$product->stock_quantity} units left.", ['url' => '/admin/low-stock']);
            if ($product->seller_id) {
                NotificationHelper::send($product->seller_id, 'stock.low', 'Low Stock Alert! ⚠️', "Your product '{$product->name}' has only {$product->stock_quantity} units left.", ['url' => '/seller/products']);
            }
        }
    }
}
