<?php

namespace App\Jobs;

use App\Models\Newsletter;
use App\Models\User;
use App\Mail\MarketingMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $newsletter;
    protected $user;

    public function __construct(Newsletter $newsletter, User $user)
    {
        $this->newsletter = $newsletter;
        $this->user = $user;
    }

    public function handle()
    {
        Mail::to($this->user->email)->send(new MarketingMail(
            $this->newsletter->subject,
            $this->newsletter->content
        ));
    }
}
