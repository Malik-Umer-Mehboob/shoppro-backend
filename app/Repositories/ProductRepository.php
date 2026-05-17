<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /**
     * Filter and paginate products.
     */
    public function getFilteredProducts(array $filters, $user = null): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Base selection for performance
        $query->select(
            'id', 'name', 'slug', 'price', 'sale_price',
            'thumbnail', 'category_id', 'seller_id',
            'stock_quantity', 'status', 'is_featured',
            'sku', 'short_description', 'created_at',
            'moderation_status'
        );

        $isAdmin = $user && $user->hasRole('admin');
        $isSeller = $user && $user->hasRole('seller');

        if ($isAdmin) {
            $query->with(['category:id,name,slug', 'seller:id,name']);
        } elseif ($isSeller) {
            $query->where('seller_id', $user->id)
                  ->with(['category:id,name,slug']);
        } else {
            $query->where('status', 'published')
                  ->where('moderation_status', 'approved')
                  ->with(['category:id,name,slug']);
        }

        // Optimization: Eager load review stats
        $query->withCount(['reviews as total_reviews' => function ($query) {
            $query->where('status', 'approved');
        }]);
        $query->withAvg(['reviews as average_rating' => function ($query) {
            $query->where('status', 'approved');
        }], 'rating');

        // Apply dynamic filters
        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($filters['per_page'] ?? 12, ['*'], 'page', $filters['page'] ?? null);
    }

    /**
     * Get products optimized for the homepage.
     */
    public function getHomepageProducts(int $limit = 8): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->query()
            ->select(
                'id', 'name', 'slug', 'price', 'sale_price',
                'thumbnail', 'category_id', 'status', 'moderation_status', 'is_featured'
            )
            ->with(['category:id,name,slug'])
            ->where('status', 'published')
            ->where('moderation_status', 'approved')
            ->withCount(['reviews as total_reviews' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->withAvg(['reviews as average_rating' => function ($query) {
                $query->where('status', 'approved');
            }], 'rating')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Get stats for products.
     */
    public function getProductStats($user = null): array
    {
        $isAdmin = $user && $user->hasRole('admin');
        $isSeller = $user && $user->hasRole('seller');

        $query = $this->model->query();

        if ($isSeller && !$isAdmin) {
            $query->where('seller_id', $user->id);
        } elseif (!$isAdmin && !$isSeller) {
            $query->where('status', 'published')->where('moderation_status', 'approved');
        }

        return (array) $query->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived,
            SUM(CASE WHEN moderation_status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN moderation_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) as low_stock
        ")->first()->toArray();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['moderation_status'])) {
            $query->where('moderation_status', $filters['moderation_status']);
        }

        if (!empty($filters['category_id'])) {
            $categoryIds = [$filters['category_id']];
            $children = \App\Models\Category::where('parent_id', $filters['category_id'])->pluck('id')->toArray();
            $categoryIds = array_merge($categoryIds, $children);
            
            if (!empty($children)) {
                $grandchildren = \App\Models\Category::whereIn('parent_id', $children)->pluck('id')->toArray();
                $categoryIds = array_merge($categoryIds, $grandchildren);
            }
            
            $query->whereIn('category_id', $categoryIds);
        }
    }
}
