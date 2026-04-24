<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generateInvoiceFor(Order $order): Invoice
    {
        // Calculate totals if not already saved
        $subtotal = $order->subtotal;
        $shipping = $order->shipping_cost;
        $tax = $order->tax;
        $discount = $order->discount;
        $total = $order->grand_total;

        $invoice = Invoice::updateOrCreate(
            ['order_id' => $order->id],
            [
                'billed_to' => $order->billing_address,
                'shipped_to' => $order->shipping_address,
                'sub_total' => $subtotal,
                'shipping_cost' => $shipping,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
            ]
        );

        return $invoice;
    }

    public function generateInvoicePDF(Invoice $invoice)
    {
        $invoice->load(['order.items.product', 'order.user']);

        // Since we don't have a specific blade view yet, we can create one or pass data
        $pdf = Pdf::loadView('invoices.template', [
            'invoice' => $invoice,
            'order' => $invoice->order,
            'company' => [
                'name' => 'ShopPro Inc.',
                'address' => '123 E-commerce Blvd, Web City, WW 12345',
                'email' => 'support@shoppro.com',
                'phone' => '+1 (555) 123-4567'
            ]
        ]);

        return $pdf;
    }

    public function downloadInvoicePDF($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $pdf = $this->generateInvoicePDF($invoice);
        
        return $pdf->download($invoice->invoice_number . '.pdf');
    }
}
