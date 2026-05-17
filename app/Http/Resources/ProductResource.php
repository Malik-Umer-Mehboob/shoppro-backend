<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $avgRating = $this->average_rating ?? 0;
        $reviewCount = $this->total_reviews ?? 0;

        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'short_description'   => $this->short_description,
            'price'               => $this->price,
            'sale_price'          => $this->sale_price,
            'sku'                 => $this->sku,
            'stock_quantity'      => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'status'              => $this->status,
            'moderation_status'   => $this->moderation_status,
            'is_featured'         => (bool) $this->is_featured,
            'category_id'         => $this->category_id,
            'thumbnail'           => $this->formatThumbnail(),
            'category'            => $this->category?->name,
            'seller'              => $this->seller?->name,
            'images'              => $this->images->map(fn($img) => [
                'id'         => $img->id,
                'url'        => asset('storage/' . $img->image_path),
                'is_primary' => (bool) $img->is_primary,
            ]),
            'variants'            => $this->variants,
            'tags'                => $this->tags ?? [],
            'average_rating'      => round($avgRating ?? 0, 1),
            'total_reviews'       => $reviewCount,
            'seo'                 => $this->generateSeoMetadata($avgRating, $reviewCount),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }

    protected function formatThumbnail()
    {
        if (!$this->thumbnail) return null;
        
        if (str_starts_with(trim($this->thumbnail), 'http')) {
            return trim($this->thumbnail);
        }
        
        return asset('storage/' . trim($this->thumbnail));
    }

    protected function generateSeoMetadata($avgRating, $reviewCount)
    {
        return [
            'title'         => $this->name . ' | ShopPro',
            'description'   => $this->short_description ?? substr(strip_tags($this->description ?? ''), 0, 160),
            'keywords'      => implode(', ', array_filter([$this->name, $this->category?->name, $this->sku])),
            'og_image'      => $this->formatThumbnail(),
            'canonical_url' => config('app.frontend_url', 'http://localhost:5173') . '/products/' . $this->id,
            'schema'        => [
                '@context'        => 'https://schema.org',
                '@type'           => 'Product',
                'name'            => $this->name,
                'description'     => $this->short_description,
                'sku'             => $this->sku,
                'offers'          => [
                    '@type'         => 'Offer',
                    'price'         => $this->sale_price ?? $this->price,
                    'priceCurrency' => 'PKR',
                    'availability'  => $this->stock_quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                ],
                'aggregateRating' => $reviewCount > 0 ? [
                    '@type'       => 'AggregateRating',
                    'ratingValue' => round($avgRating, 1),
                    'reviewCount' => $reviewCount,
                ] : null,
            ],
        ];
    }
}
