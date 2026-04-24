<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    /**
     * GET /api/orders/{id}/invoice
     * Generate or retrieve invoice for an order.
     */
    public function show(Request $request, $orderId)
    {
        $user  = $request->user();
        $order = Order::with(['user', 'items.product', 'invoice'])->findOrFail($orderId);

        // Authorise: admin sees all; seller sees own; customer sees own
        if ($user->hasRole('seller') && $order->seller_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        if ($user->hasRole('customer') && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Generate invoice if missing
        if (!$order->invoice) {
            $this->invoiceService->generateInvoiceFor($order);
            $order->refresh();
        }

        return response()->json([
            'invoice' => $order->invoice,
            'order'   => $order,
        ]);
    }

    /**
     * GET /api/orders/{id}/invoice/download
     */
    public function download(Request $request, $orderId)
    {
        $user  = $request->user();
        $order = Order::with(['user', 'items.product', 'invoice'])->findOrFail($orderId);

        // Authorise
        if ($user->hasRole('seller') && $order->seller_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        if ($user->hasRole('customer') && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (!$order->invoice) {
            $this->invoiceService->generateInvoiceFor($order);
            $order->refresh();
        }

        return $this->invoiceService->downloadInvoicePDF($order->invoice->id);
    }
}
