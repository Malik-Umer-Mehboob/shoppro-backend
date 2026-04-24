<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\BlogService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    public function index(Request $request)
    {
        $query = BlogPost::with(['author', 'category', 'tags'])
            ->where('status', 'published')
            ->latest();

        if ($request->has('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->has('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $request->tag));
        }

        if ($request->has('search')) {
            $query->where('title', 'like', "%{$request->search}%")
                  ->orWhere('content', 'like', "%{$request->search}%");
        }

        return response()->json($query->paginate(10));
    }

    public function show($slug)
    {
        $post = BlogPost::with(['author', 'category', 'tags', 'approvedComments.user', 'approvedComments.children.user'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = $this->blogService->getRelatedPosts($post);
        $sidebar = $this->blogService->getSidebarData();

        return response()->json([
            'post' => $post,
            'related' => $related,
            'sidebar' => $sidebar,
        ]);
    }

    public function adminIndex(Request $request)
    {
        $this->authorize('manage-blog');
        $query = BlogPost::with(['author', 'category'])->latest();
        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $this->authorize('create-blog');
        
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'status' => 'required|in:draft,published,scheduled',
        ]);

        $data = $request->all();
        $data['slug'] = $this->blogService->generateUniqueSlug($request->title);
        $data['author_id'] = $request->user()->id;
        
        if ($request->status === 'published') {
            $data['published_at'] = now();
        }

        $post = BlogPost::create($data);

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        return response()->json($post, 201);
    }

    public function update(Request $request, BlogPost $post)
    {
        $this->authorize('update', $post);
        
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'status' => 'sometimes|in:draft,published,scheduled',
        ]);

        $data = $request->all();
        if ($request->has('title') && $request->title !== $post->title) {
            $data['slug'] = $this->blogService->generateUniqueSlug($request->title, $post->id);
        }

        $post->update($data);

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        return response()->json($post);
    }

    public function rss()
    {
        $posts = BlogPost::where('status', 'published')->latest()->limit(20)->get();
        
        $rss = '<?xml version="1.0" encoding="UTF-8" ?>';
        $rss .= '<rss version="2.0"><channel>';
        $rss .= '<title>ShopPro Blog</title>';
        $rss .= '<link>' . config('app.url') . '</link>';
        $rss .= '<description>Latest updates from ShopPro</description>';
        
        foreach ($posts as $post) {
            $rss .= '<item>';
            $rss .= '<title>' . e($post->title) . '</title>';
            $rss .= '<link>' . config('app.url') . '/blog/' . $post->slug . '</link>';
            $rss .= '<description>' . e($post->excerpt) . '</description>';
            $rss .= '<pubDate>' . $post->published_at->toRssString() . '</pubDate>';
            $rss .= '</item>';
        }
        
        $rss .= '</channel></rss>';
        
        return response($rss, 200)->header('Content-Type', 'text/xml');
    }
}
