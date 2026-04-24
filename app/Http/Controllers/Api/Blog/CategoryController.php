<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(BlogCategory::withCount('posts')->get());
    }

    public function store(Request $request)
    {
        $this->authorize('manage-blog');
        $request->validate(['name' => 'required|string|unique:blog_categories,name']);

        $category = BlogCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json($category, 201);
    }
}
