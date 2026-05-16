<?php

namespace App\Events;

use App\Models\BlogComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentPosted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;

    public function __construct(BlogComment $comment)
    {
        $this->comment = $comment;
    }
}
