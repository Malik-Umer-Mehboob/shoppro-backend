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
        .company-info p { margin: 0; line-height: 1.5; color: #666; }
        .invoice-details { text-align: right; }
        .invoice-details h1 { margin: 0 0 10px 0; color: #333; font-size: 24px; text-transform: uppercase; }
        .invoice-details p { margin: 0; line-height: 1.5; color: #666; }
        .addresses { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .address-block { width: 48%; }
        .address-block h3 { margin: 0 0 10px 0; color: #333; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .address-block p { margin: 0; line-height: 1.5; color: #666; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .items-table th, .items-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .items-table th { background-color: #f8f9fa; color: #333; font-weight: bold; }
        .items-table .text-right { text-align: right; }
        .totals { width: 50%; float: right; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table th, .totals-table td { padding: 8px 12px; text-align: right; }
        .totals-table .grand-total th, .totals-table .grand-total td { font-size: 18px; font-weight: bold; color: #4F46E5; border-top: 2px solid #eee; padding-top: 15px; }
        .footer { clear: both; margin-top: 50px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="company-info">
                <h2>{{ $company['name'] }}</h2>
                <p>{{ $company['address'] }}</p>
                <p>{{ $company['email'] }}</p>
                <p>{{ $company['phone'] }}</p>
            </div>
            <div class="invoice-details">
                <h1>INVOICE</h1>
                <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $invoice->created_at->format('M d, Y') }}</p>
                <p><strong>Order #:</strong> {{ $order->id }}</p>
                <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
            </div>
        </div>

        <div class="addresses">
            <div class="address-block">
                <h3>Billed To</h3>
                @if($invoice->billed_to)
                    <p><strong>{{ $invoice->billed_to['first_name'] ?? '' }} {{ $invoice->billed_to['last_name'] ?? '' }}</strong></p>
                    <p>{{ $invoice->billed_to['address_line_1'] ?? '' }}</p>
                    <p>{{ $invoice->billed_to['address_line_2'] ?? '' }}</p>
                    <p>{{ $invoice->billed_to['city'] ?? '' }}, {{ $invoice->billed_to['state'] ?? '' }} {{ $invoice->billed_to['postal_code'] ?? '' }}</p>
                    <p>{{ $invoice->billed_to['country'] ?? '' }}</p>
                @endif
            </div>
            <div class="address-block">
                <h3>Shipped To</h3>
                @if($invoice->shipped_to)
                    <p><strong>{{ $invoice->shipped_to['first_name'] ?? '' }} {{ $invoice->shipped_to['last_name'] ?? '' }}</strong></p>
                    <p>{{ $invoice->shipped_to['address_line_1'] ?? '' }}</p>
                    <p>{{ $invoice->shipped_to['address_line_2'] ?? '' }}</p>
                    <p>{{ $invoice->shipped_to['city'] ?? '' }}, {{ $invoice->shipped_to['state'] ?? '' }} {{ $invoice->shipped_to['postal_code'] ?? '' }}</p>
                    <p>{{ $invoice->shipped_to['country'] ?? '' }}</p>
                @endif
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
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
        </div>
    </div>
</body>
</html>
