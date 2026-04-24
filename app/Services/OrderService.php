<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\LoyaltyController;

class OrderService
{
    /**
     * Create an order from the current cart.
     */
    public function createFromCart(Cart $cart, array $shippingAddress, string $paymentMethod, ?string $paymentId = null): Order
    {
        return DB::transaction(function () use ($cart, $shippingAddress, $paymentMethod, $paymentId) {
            $cart->load(['items.product', 'items.variant']);

            // Determine seller (if single-vendor, null; multi-vendor: first item's seller)
            $sellerId = null;
            $firstProduct = $cart->items->first()?->product;
            if ($firstProduct) {
                $sellerId = $firstProduct->seller_id;
            }

            $order = Order::create([
                'user_id'          => $cart->user_id,
                'seller_id'        => $sellerId,
                'status'           => Order::STATUS_PENDING,
                'total_items'      => $cart->total_items,
                'total_quantity'   => $cart->total_quantity,
                'subtotal'         => $cart->subtotal,
                'shipping_address' => $shippingAddress,
                'billing_address'  => $shippingAddress,
                'payment_method'   => $paymentMethod,
                'payment_id'       => $paymentId,
                'payment_status'   => $paymentMethod === 'cod' ? Order::PAYMENT_PENDING : Order::PAYMENT_PAID,
                'shipping_cost'    => $cart->shipping_amount ?? 0,
                'tax'              => $cart->tax_amount ?? 0,
                'coupon_code'      => $cart->coupon_code,
                'discount'         => $cart->discount_amount ?? 0,
                'grand_total'      => $cart->total,
            ]);

            // Create order items & reduce inventory
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'name'       => $item->product?->name ?? 'Product',
                    'quantity'   => $item->quantity,
                    'price'      => $item->price,
                    'total'      => $item->price * $item->quantity,
                ]);

                // Reduce inventory
                if ($item->variant_id) {
                    $item->variant?->decrement('stock_quantity', $item->quantity);
                } elseif ($item->product_id) {
                    Product::where('id', $item->product_id)
                        ->decrement('stock_quantity', $item->quantity);
                }
            }

            // Mark cart as completed
            $cart->update(['status' => 'completed']);

            // Generate invoice
            $this->generateInvoice($order);

            // Award loyalty points
            if ($order->user_id) {
                LoyaltyController::awardPoints(
                    $order->user_id, 
                    $order->grand_total, 
                    "Points earned from order #{$order->id}"
                );
            }

            // Fire event
            event(new OrderPlaced($order));

            return $order->fresh(['items', 'invoice']);
        });
    }

    /**
     * Generate an invoice for an order.
     */
    public function generateInvoice(Order $order): Invoice
    {
        return Invoice::firstOrCreate(
            ['order_id' => $order->id],
            ['total'    => $order->grand_total]
        );
    }

    /**
     * Restore inventory when an order is refunded/returned.
     */
    public function restoreInventory(Order $order): void
    {
        $order->load('items');

        foreach ($order->items as $item) {
            if ($item->variant_id) {
                $item->variant?->increment('stock_quantity', $item->quantity);
            } elseif ($item->product_id) {
                Product::where('id', $item->product_id)
                    ->increment('stock_quantity', $item->quantity);
            }
        }
    }
}
