<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Order;
use App\Models\EmailLog;
use App\Mail\OrderConfirmationMail;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): void
    {
        $log = EmailLog::create([
            'recipient_email' => $this->order->customer->email,
            'template_name' => 'OrderConfirmationMail',
            'status' => 'pending',
            'user_id' => $this->order->customer->id,
        ]);

        try {
            Mail::to($this->order->customer->email)->send(new OrderConfirmationMail($this->order));
            $log->update(['status' => 'sent']);
            Log::info("Email dispatched: OrderConfirmation", [$this->order->id]);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error("Email failed", [$e->getMessage()]);
            
            try {
                // Fallback direct mail
                Mail::raw("Your order #{$this->order->order_number} has been confirmed.", function ($message) {
                    $message->to($this->order->customer->email)->subject("Order Confirmation - " . $this->order->order_number);
                });
            } catch (\Exception $e2) {
                Log::error("Email fallback failed", [$e2->getMessage()]);
            }

            throw $e;
        }
    }
}
