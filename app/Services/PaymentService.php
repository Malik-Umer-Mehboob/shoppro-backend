<?php

namespace App\Services;

use App\Models\Order;

class PaymentService
{
    /**
     * Process payment for an order.
     * For COD: no processing needed, just mark as pending.
     * For Stripe/PayPal: integrate actual gateway here.
     */
    public function processPayment(Order $order, array $paymentData = []): array
    {
        if ($order->payment_method === 'cod') {
            return ['success' => true, 'message' => 'Cash on delivery order placed.'];
        }

        if ($order->payment_method === 'stripe') {
            return $this->processStripePayment($order, $paymentData);
        }

        if ($order->payment_method === 'paypal') {
            return $this->processPayPalPayment($order, $paymentData);
        }

        return ['success' => false, 'message' => 'Unsupported payment method.'];
    }

    /**
     * Process a refund for an order.
     */
    public function processRefund(Order $order): array
    {
        if ($order->payment_method === 'cod') {
            // For COD: manual refund — just update status
            $order->update([
                'status'         => Order::STATUS_REFUNDED,
                'payment_status' => Order::PAYMENT_REFUNDED,
            ]);
            return ['success' => true, 'message' => 'COD refund processed manually.'];
        }

        if ($order->payment_method === 'stripe' && $order->payment_id) {
            return $this->processStripeRefund($order);
        }

        // Generic fallback
        $order->update([
            'status'         => Order::STATUS_REFUNDED,
            'payment_status' => Order::PAYMENT_REFUNDED,
        ]);

        return ['success' => true, 'message' => 'Refund processed.'];
    }

    // ---------------------
    // Private Stripe Methods
    // ---------------------

    private function processStripePayment(Order $order, array $paymentData): array
    {
        try {
            $stripeSecret = config('services.stripe.secret');
            if (!$stripeSecret) {
                return ['success' => false, 'message' => 'Stripe not configured.'];
            }

            // Stripe payment intent confirmation would go here when Stripe SDK is added
            // \Stripe\Stripe::setApiKey($stripeSecret);
            // $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentData['payment_intent_id']);

            $order->update([
                'payment_id'     => $paymentData['payment_intent_id'] ?? null,
                'payment_status' => Order::PAYMENT_PAID,
            ]);

            return ['success' => true, 'message' => 'Stripe payment confirmed.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function processStripeRefund(Order $order): array
    {
        try {
            // \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            // \Stripe\Refund::create(['payment_intent' => $order->payment_id]);

            $order->update([
                'status'         => Order::STATUS_REFUNDED,
                'payment_status' => Order::PAYMENT_REFUNDED,
            ]);

            return ['success' => true, 'message' => 'Stripe refund processed.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function processPayPalPayment(Order $order, array $paymentData): array
    {
        // PayPal integration stub
        $order->update([
            'payment_id'     => $paymentData['paypal_order_id'] ?? null,
            'payment_status' => Order::PAYMENT_PAID,
        ]);

        return ['success' => true, 'message' => 'PayPal payment confirmed.'];
    }
}
