<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    /**
     * Display a listing of the blog posts.
     */
    public function index()
    {
        $posts = BlogPost::with(['author', 'category'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    /**
     * Store a newly created blog post.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:blog_categories,id',
            'status' => 'required|in:draft,published',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $validated['author_id'] = auth()->id();
        $validated['slug'] = Str::slug($request->title) . '-' . Str::lower(Str::random(4));

        if ($request->status === 'published') {
            $validated['published_at'] = now();
        }

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('blog', 'public');
            $validated['thumbnail'] = $path;
        }

        $post = BlogPost::create($validated);

        if (!empty($request->tags)) {
            // Assuming BlogPost has a tags() relationship or stores tags as JSON/string
            // If it's a relationship:
            // $post->tags()->sync($request->tags);
        }

        return response()->json([
            'success' => true,
            'message' => 'Blog post created successfully',
            'data' => $post,
        ], 201);
    }

    /**
     * Get all blog categories.
     */
    public function getCategories()
    {
        $categories = BlogCategory::all(['id', 'name']);
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Remove the specified blog post.
     */
    public function destroy($id)
    {
        $post = BlogPost::findOrFail($id);
        
        if ($post->thumbnail) {
            Storage::disk('public')->delete($post->thumbnail);
        }
        
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blog post deleted successfully'
        ]);
    }
}
