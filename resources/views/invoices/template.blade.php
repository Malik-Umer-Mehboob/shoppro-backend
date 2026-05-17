<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .company-info h2 { margin: 0 0 10px 0; color: #4F46E5; }
        .company-info .logo-text { font-size: 28px; font-weight: bold; color: #4F46E5; margin-bottom: 5px; }
        .company-info p { margin: 0; line-height: 1.5; color: #666; }
        .invoice-details { text-align: right; }
        .invoice-details h1 { margin: 0 0 10px 0; color: #333; font-size: 24px; text-transform: uppercase; }
        .invoice-details p { margin: 0; line-height: 1.5; color: #666; }
        .addresses { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .address-block { width: 48%; }
        .address-block h3 { margin: 0 0 10px 0; color: #333; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .address-block p { margin: 0; line-height: 1.5; color: #666; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .items-table th, .items-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        .items-table th { background-color: #f8f9fa; color: #333; font-weight: bold; }
        .items-table .text-right { text-align: right; }
        .product-img { max-width: 50px; border-radius: 4px; }
        .totals { width: 50%; float: right; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table th, .totals-table td { padding: 8px 12px; text-align: right; }
        .totals-table .grand-total th, .totals-table .grand-total td { font-size: 18px; font-weight: bold; color: #4F46E5; border-top: 2px solid #eee; padding-top: 15px; }
        .footer { clear: both; margin-top: 50px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table style="width: 100%; margin-bottom: 40px;">
            <tr>
                <td style="vertical-align: top;" class="company-info">
                    <div class="logo-text">ShopPro</div>
                    <h2>{{ $company['name'] }}</h2>
                    <p>{{ $company['address'] }}</p>
                    <p>{{ $company['email'] }}</p>
                    <p>{{ $company['phone'] }}</p>
                </td>
                <td style="vertical-align: top; text-align: right;" class="invoice-details">
                    <h1>INVOICE</h1>
                    <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                    <p><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y') }}</p>
                    <p><strong>Order #:</strong> {{ $order->id }}</p>
                    <p><strong>Payment Method:</strong> {{ strtoupper($order->payment_method ?? 'N/A') }}</p>
                    <p><strong>Payment Status:</strong> {{ $invoice->isPaid() ? 'Paid' : ucfirst($order->payment_status ?? 'Pending') }}</p>
                    <p><strong>Delivery Status:</strong> {{ ucfirst($order->status) }}</p>
                </td>
            </tr>
        </table>

        <table style="width: 100%; margin-bottom: 40px;">
            <tr>
                <td style="width: 48%; vertical-align: top;" class="address-block">
                    <h3>Billed To</h3>
                    @if($invoice->billed_to)
                        <p><strong>{{ $invoice->billed_to['full_name'] ?? ($invoice->billed_to['first_name'] ?? '') . ' ' . ($invoice->billed_to['last_name'] ?? '') }}</strong></p>
                        <p>Email: {{ $order->user->email ?? 'N/A' }}</p>
                        <p>Phone: {{ $invoice->billed_to['phone'] ?? 'N/A' }}</p>
                        <p>{{ $invoice->billed_to['address_line_1'] ?? '' }}</p>
                        <p>{{ $invoice->billed_to['address_line_2'] ?? '' }}</p>
                        <p>{{ $invoice->billed_to['city'] ?? '' }}, {{ $invoice->billed_to['state'] ?? '' }} {{ $invoice->billed_to['postal_code'] ?? '' }}</p>
                        <p>{{ $invoice->billed_to['country'] ?? '' }}</p>
                    @endif
                </td>
                <td style="width: 4%;">&nbsp;</td>
                <td style="width: 48%; vertical-align: top;" class="address-block">
                    <h3>Shipped To</h3>
                    @if($invoice->shipped_to)
                        <p><strong>{{ $invoice->shipped_to['full_name'] ?? ($invoice->shipped_to['first_name'] ?? '') . ' ' . ($invoice->shipped_to['last_name'] ?? '') }}</strong></p>
                        <p>Phone: {{ $invoice->shipped_to['phone'] ?? 'N/A' }}</p>
                        <p>{{ $invoice->shipped_to['address_line_1'] ?? '' }}</p>
                        <p>{{ $invoice->shipped_to['address_line_2'] ?? '' }}</p>
                        <p>{{ $invoice->shipped_to['city'] ?? '' }}, {{ $invoice->shipped_to['state'] ?? '' }} {{ $invoice->shipped_to['postal_code'] ?? '' }}</p>
                        <p>{{ $invoice->shipped_to['country'] ?? '' }}</p>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Item Description</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        @if($item->product && $item->product->primary_image)
                            <!-- Placeholder image if real URL not accessible by dompdf -->
                            <span style="display:inline-block; width: 40px; height: 40px; background: #eee; text-align: center; line-height: 40px; color: #999; font-size: 10px;">IMG</span>
                        @else
                            <span style="display:inline-block; width: 40px; height: 40px; background: #eee; text-align: center; line-height: 40px; color: #999; font-size: 10px;">IMG</span>
                        @endif
                    </td>
                    <td>
                        {{ $item->name }}<br>
                        <small style="color: #999;">SKU: {{ $item->product->sku ?? 'N/A' }}</small>
                    </td>
                    <td>${{ number_format($item->price, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table class="totals-table">
                <tr>
                    <th>Subtotal:</th>
                    <td>${{ number_format($invoice->sub_total, 2) }}</td>
                </tr>
                <tr>
                    <th>Shipping:</th>
                    <td>${{ number_format($invoice->shipping_cost, 2) }}</td>
                </tr>
                <tr>
                    <th>Tax:</th>
                    <td>${{ number_format($invoice->tax, 2) }}</td>
                </tr>
                @if($invoice->discount > 0)
                <tr>
                    <th>Discount:</th>
                    <td>-${{ number_format($invoice->discount, 2) }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <th>Grand Total:</th>
                    <td>${{ number_format($invoice->total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact us at {{ $company['email'] }}</p>
            <p><small>Generated at {{ now()->format('Y-m-d H:i:s T') }}</small></p>
        </div>
    </div>
</body>
</html>
