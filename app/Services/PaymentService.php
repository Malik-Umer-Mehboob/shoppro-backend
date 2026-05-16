<?php
namespace App\Services;

use App\Models\Order;

class PaymentService
{
    public function processCOD(Order $order): array
    {
        $order->update([
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'payment_notes' => 'Cash to be collected on delivery',
        ]);

        return [
            'success' => true,
            'message' => 'Order placed successfully. Pay on delivery.',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ];
    }

    public function processBankTransfer(Order $order, string $referenceNumber): array
    {
        $order->update([
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'payment_id' => $referenceNumber,
            'payment_notes' => 'Awaiting bank transfer verification',
        ]);

        return [
            'success' => true,
            'message' => 'Order placed. Please transfer amount to our bank account.',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'bank_details' => [
                'bank_name' => 'HBL Bank',
                'account_title' => 'ShopPro Pvt Ltd',
                'account_number' => '1234-5678-9012',
                'iban' => 'PK36HABB0000001234567890',
                'reference' => 'Order #' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
            ],
        ];
    }

    public function markAsPaid(Order $order, string $paymentId = null): array
    {
        $order->update([
            'payment_status' => 'paid',
            'payment_id' => $paymentId ?? $order->payment_id,
        ]);

        event(new \App\Events\PaymentVerified($order));

        return ['success' => true, 'message' => 'Payment marked as paid'];
    }

    public function processRefund(Order $order, string $reason = null): array
    {
        \Illuminate\Support\Facades\Log::info('Processing refund for order #' . $order->id, ['reason' => $reason]);

        $order->update([
            'status' => 'refunded',
            'payment_status' => 'refunded',
            'payment_notes' => $reason ?? 'Refund processed by admin',
        ]);

        // Create or update ReturnRequest record for sync
        $returnRequest = \App\Models\ReturnRequest::updateOrCreate(
            ['order_id' => $order->id],
            [
                'user_id' => $order->user_id,
                'reason' => 'other',
                'description' => $reason ?? 'Full order refund processed by admin',
                'status' => 'refunded',
                'refund_type' => 'full_refund',
                'refund_amount' => $order->grand_total,
                'refunded_at' => now(),
                'approved_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\Log::info('ReturnRequest created/updated for sync', ['id' => $returnRequest->id]);

        return ['success' => true, 'message' => 'Refund processed and synced with Return Requests'];
    }

    public function getStripeIntent(Order $order): array
    {
        // Stripe placeholder - ready for future integration
        return [
            'success' => false,
            'message' => 'Stripe integration coming soon',
        ];
    }
}
