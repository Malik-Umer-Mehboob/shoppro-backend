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

        return ['success' => true, 'message' => 'Payment marked as paid'];
    }

    public function processRefund(Order $order, string $reason = null): array
    {
        $order->update([
            'payment_status' => 'refunded',
            'payment_notes' => $reason ?? 'Refund processed by admin',
        ]);

        return ['success' => true, 'message' => 'Refund processed successfully'];
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
