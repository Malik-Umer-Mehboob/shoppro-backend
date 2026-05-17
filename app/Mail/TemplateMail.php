<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\EmailLog;
use App\Models\User;

class TemplateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    
    public $subject;
    public $content;
    public $templateName;
    public $recipientEmail;

    public function __construct($subject, $content, $templateName, $recipientEmail)
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->templateName = $templateName;
        $this->recipientEmail = $recipientEmail;
    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->html($this->content);
    }

    public function failed(\Throwable $exception)
    {
        EmailLog::create([
            'recipient_email' => $this->recipientEmail,
            'template_name' => $this->templateName,
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
