<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCommentPosted implements ShouldQueue
{
    public function handle(CommentPosted $event): void
    {
        $comment = $event->comment;
        $post = $comment->post;

        // Notify Admins for moderation
        NotificationService::notifyAdmins(
            'New Blog Comment! 💬',
            "User {$comment->user->name} commented on '{$post->title}'.",
            'blog.comment',
            NotificationService::PRIORITY_LOW,
            ['comment_id' => $comment->id],
            '/admin/blog/comments'
        );
    }
}
