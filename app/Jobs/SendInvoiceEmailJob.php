<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\Order;
use App\Models\EmailLog;
use App\Services\InvoiceService;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(InvoiceService $invoiceService): void
    {
        if (!$this->order->invoice) {
            $invoiceService->generateInvoiceFor($this->order);
            $this->order->refresh();
        }

        $log = EmailLog::create([
            'recipient_email' => $this->order->user->email,
            'template_name' => 'InvoiceEmail',
            'status' => 'pending',
            'user_id' => $this->order->user->id,
        ]);

        try {
            $pdf = $invoiceService->generateInvoicePDF($this->order->invoice);
            $pdfContent = $pdf->output();
            
            Mail::raw("Dear Customer,\n\nThank you for your order. Please find your invoice attached.\n\nBest Regards,\nShopPro Team", function ($message) use ($pdfContent) {
                $message->to($this->order->user->email)
                        ->subject('Your ShopPro Invoice - Order #' . $this->order->id)
                        ->attachData($pdfContent, 'Invoice-' . $this->order->invoice->invoice_number . '.pdf', [
                            'mime' => 'application/pdf',
                        ]);
            });
            $log->update(['status' => 'sent']);
            \Illuminate\Support\Facades\Log::info("Email dispatched: InvoiceEmail", [$this->order->id]);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            \Illuminate\Support\Facades\Log::error("Email failed", [$e->getMessage()]);
            
            try {
                // Fallback direct mail without attachment if invoice generation fails
                \Illuminate\Support\Facades\Mail::raw("Your invoice for order #{$this->order->order_number} is available in your account.", function ($message) {
                    $message->to($this->order->user->email)->subject("Your Invoice - Order " . $this->order->order_number);
                });
            } catch (\Exception $e2) {
                \Illuminate\Support\Facades\Log::error("Email fallback failed", [$e2->getMessage()]);
            }
            
            throw $e;
        }
    }
}
