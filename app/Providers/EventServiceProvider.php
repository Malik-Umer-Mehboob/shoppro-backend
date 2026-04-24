<?php

namespace App\Providers;

use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderRefunded;
use App\Events\OrderShipped;
use App\Listeners\NotifyAdmin;
use App\Listeners\NotifySeller;
use App\Listeners\RequestFeedback;
use App\Listeners\SendOrderConfirmationEmail;
use App\Listeners\SendRefundEmail;
use App\Listeners\SendShipmentEmail;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Listeners\NotifyTicketCreated;
use App\Listeners\NotifyTicketUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            SendOrderConfirmationEmail::class,
            NotifyAdmin::class,
            NotifySeller::class,
        ],
        OrderShipped::class => [
            SendShipmentEmail::class,
        ],
        OrderDelivered::class => [
            RequestFeedback::class,
        ],
        OrderRefunded::class => [
            SendRefundEmail::class,
        ],
        TicketCreated::class => [
            NotifyTicketCreated::class,
        ],
        TicketUpdated::class => [
            NotifyTicketUpdated::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
