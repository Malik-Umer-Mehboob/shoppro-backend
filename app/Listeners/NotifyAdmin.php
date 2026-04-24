<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Support\Facades\Log;

class NotifyAdmin
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        // In production: send push/email notification to admin
        Log::info("New order placed: #{$order->id}, Total: {$order->grand_total}");
    }
}
