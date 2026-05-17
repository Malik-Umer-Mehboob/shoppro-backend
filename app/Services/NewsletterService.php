<?php

namespace App\Services;

use App\Models\Newsletter;
use App\Models\User;
use App\Jobs\SendNewsletterJob;
use Carbon\Carbon;

class NewsletterService
{
    public function subscribe(User $user)
    {
        $user->update(['subscribed_to_newsletter' => true]);
        return $user;
    }

    public function unsubscribe(User $user)
    {
        $user->update(['subscribed_to_newsletter' => false]);
        return $user;
    }

    public function createNewsletter(array $data)
    {
        return Newsletter::create($data);
    }

    public function scheduleNewsletter($newsletterId, $scheduledAt)
    {
        $newsletter = Newsletter::findOrFail($newsletterId);
        $newsletter->update([
            'scheduled_at' => Carbon::parse($scheduledAt),
            'status'       => 'scheduled'
        ]);
        return $newsletter;
    }

    public function sendNewsletterToSubscribers($newsletterId)
    {
        $newsletter = Newsletter::findOrFail($newsletterId);
        $newsletter->update(['status' => 'sending']);

        User::where('subscribed_to_newsletter', true)->chunk(500, function ($users) use ($newsletter) {
            foreach ($users as $user) {
                SendNewsletterJob::dispatch($newsletter, $user);
            }
        });

        $newsletter->update(['status' => 'sent']);
        return $newsletter;
    }
}
