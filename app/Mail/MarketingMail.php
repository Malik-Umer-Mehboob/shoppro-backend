<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MarketingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $content;
    public $subject;
    public $campaignId;
    public $userId;

    public function __construct($subject, $content, $campaignId = null, $userId = null)
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->campaignId = $campaignId;
        $this->userId = $userId;
    }

    public function build()
    {
        $email = $this->subject($this->subject)
                    ->html($this->content);

        // Append tracking pixel if campaign and user IDs are provided
        if ($this->campaignId && $this->userId) {
            $trackingUrl = config('app.url') . "/api/analytics/open/{$this->campaignId}/{$this->userId}";
            $this->content .= "<img src='{$trackingUrl}' width='1' height='1' style='display:none;' />";
        }

        if ($this->userId) {
            $user = \App\Models\User::find($this->userId);
            if ($user) {
                $unsubscribeUrl = \App\Http\Controllers\Api\NewsletterController::getUnsubscribeUrl($user);
                $this->content .= "<p style='font-size:12px;color:#666;margin-top:30px;'>Don't want to receive these emails? <a href='{$unsubscribeUrl}'>Unsubscribe here</a></p>";
            }
        }

        return $email;
    }
}
