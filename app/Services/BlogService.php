<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Illuminate\Support\Str;

class BlogService
{
    public function generateUniqueSlug($title, $id = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (BlogPost::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    public function getRelatedPosts(BlogPost $post, $limit = 3)
    {
        return BlogPost::where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                $query->where('category_id', $post->category_id)
                    ->orWhereHas('tags', function ($q) use ($post) {
                        $q->whereIn('blog_tags.id', $post->tags->pluck('id'));
                    });
            })
            ->where('status', 'published')
            ->limit($limit)
            ->get();
    }

    public function getSidebarData()
    {
        return [
            'categories' => BlogCategory::withCount('posts')->get(),
            'popular_tags' => BlogTag::withCount('posts')->orderBy('posts_count', 'desc')->limit(10)->get(),
            'recent_posts' => BlogPost::where('status', 'published')->latest()->limit(5)->get(),
        ];
    }
}
