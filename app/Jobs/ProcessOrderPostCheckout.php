<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use App\Helpers\EmailHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrderPostCheckout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order, User $user)
    {
        $this->order = $order;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\InvoiceService $invoiceService): void
    {
        // 0. Generate Invoice automatically
        $invoiceService->generateInvoiceFor($this->order);

        // 1. Send order confirmation email
        try {
            app(\App\Services\MailService::class)->sendOrderConfirmation($this->order);
            app(\App\Services\MailService::class)->sendInvoiceEmail($this->order);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order email failed: ' . $e->getMessage());
        }

        // 2. Notify customer
        NotificationService::send(
            $this->user->id,
            'order.placed',
            'Order Placed Successfully!',
            'Your order #' . str_pad($this->order->id, 4, '0', STR_PAD_LEFT)
                . ' has been placed. Total: Rs. ' . number_format($this->order->grand_total),
            ['order_id' => $this->order->id]
        );

        // 3. Notify admins
        NotificationService::sendToAdmins(
            'new_order',
            'New Order Received',
            'Order #' . str_pad($this->order->id, 4, '0', STR_PAD_LEFT)
                . ' from ' . $this->user->name
                . ' — Rs. ' . number_format($this->order->grand_total),
            ['order_id' => $this->order->id]
        );

        // 4. Notify sellers
        NotificationService::sendToSellers(
            $this->order->id,
            'New Order for Your Product',
            'Order #' . str_pad($this->order->id, 4, '0', STR_PAD_LEFT) . ' placed',
            ['order_id' => $this->order->id]
        );

        // 5. Check low stock alerts
        foreach ($this->order->items as $item) {
            $product = $item->product;
            if ($product->stock_quantity <= ($product->low_stock_threshold ?? 5) && $product->stock_quantity > 0) {
                NotificationService::sendToAdmins(
                    'low_stock',
                    'Low Stock Alert',
                    $product->name . ' has only ' . $product->stock_quantity . ' units left',
                    ['product_id' => $product->id]
                );
            }

            if ($product->stock_quantity === 0) {
                NotificationService::sendToAdmins(
                    'out_of_stock',
                    'Out of Stock',
                    $product->name . ' is now out of stock',
                    ['product_id' => $product->id]
                );
            }
        }
    }
}
