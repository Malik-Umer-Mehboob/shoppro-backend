<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;

    public function __construct($campaign)
    {
        $this->campaign = $campaign;
    }

    public function build()
    {
        $content = $this->campaign->content;
        
        // Ensure HTML content is supported
        return $this->subject($this->campaign->subject)
                    ->html($content ? nl2br(e($content)) : '<p>' . e($this->campaign->subject) . '</p>');
    }
}
