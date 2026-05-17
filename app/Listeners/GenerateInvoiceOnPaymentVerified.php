<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Services\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GenerateInvoiceOnPaymentVerified implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(private InvoiceService $invoiceService)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentVerified $event): void
    {
        $this->invoiceService->generateInvoiceFor($event->order);
        
        // Email the invoice
        app(\App\Services\MailService::class)->sendInvoiceEmail($event->order);
    }
}
