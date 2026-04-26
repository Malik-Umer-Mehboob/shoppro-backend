<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // Process payment for an order
    public function process(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $request->validate([
            'payment_method' => 'required|in:cod,bank_transfer,stripe',
            'reference_number' => 'required_if:payment_method,bank_transfer|string|nullable',
        ]);

        $result = match($request->payment_method) {
            'cod' => $this->paymentService->processCOD($order),
            'bank_transfer' => $this->paymentService->processBankTransfer(
                $order,
                $request->reference_number ?? 'PENDING'
            ),
            'stripe' => $this->paymentService->getStripeIntent($order),
        };

        return response()->json($result);
    }

    // Admin: mark order as paid
    public function markPaid(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        $result = $this->paymentService->markAsPaid($order, $request->payment_id);
        return response()->json($result);
    }

    // Admin: process refund
    public function refund(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        $result = $this->paymentService->processRefund($order, $request->reason);
        return response()->json($result);
    }

    // Get payment status
    public function status($orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'payment_id' => $order->payment_id,
                'payment_notes' => $order->payment_notes,
            ]
        ]);
    }
}
