<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EmailOrderInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(InvoiceService $invoiceService): void
    {
        if (!$this->order->invoice) {
            $invoiceService->generateInvoiceFor($this->order);
            $this->order->refresh();
        }

        $pdf = $invoiceService->generateInvoicePDF($this->order->invoice);
        
        $pdfContent = $pdf->output();
        
        Mail::raw("Dear Customer,\n\nThank you for your order. Please find your invoice attached.\n\nBest Regards,\nShopPro Team", function ($message) use ($pdfContent) {
            $message->to($this->order->user->email)
                    ->subject('Your ShopPro Invoice - Order #' . $this->order->id)
                    ->attachData($pdfContent, 'Invoice-' . $this->order->invoice->invoice_number . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
        });
    }
}
