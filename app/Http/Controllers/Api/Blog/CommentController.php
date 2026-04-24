<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:blog_posts,id',
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:blog_comments,id',
        ]);

        $comment = BlogComment::create([
            'post_id' => $request->post_id,
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
            'status' => 'pending', // Moderation by default
        ]);

        return response()->json($comment, 201);
    }

    public function moderate(Request $request, BlogComment $comment)
    {
        $this->authorize('manage-blog');
        $request->validate(['status' => 'required|in:approved,rejected,spam']);
        
        $comment->update(['status' => $request->status]);
        return response()->json($comment);
    }
}
