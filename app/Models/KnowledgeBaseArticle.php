<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KnowledgeBaseArticle extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'category',
        'title',
        'slug',
        'content',
        'views',
        'helpful_count',
        'not_helpful_count',
        'translations',
    ];

    protected $casts = [
        'translations' => 'array',
    ];

    public static function getCategories(): array
    {
        return ['FAQ', 'Shipping & Delivery', 'Returns & Exchanges', 'Payment', 'Account Management'];
    }

    public function scopeFilterByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function incrementViews()
    {
        $this->increment('views');
    }

    public function markAsHelpful()
    {
        $this->increment('helpful_count');
    }

    public function markAsNotHelpful()
    {
        $this->increment('not_helpful_count');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }
}
