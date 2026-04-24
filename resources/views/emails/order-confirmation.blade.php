<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmed</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f8; margin: 0; padding: 20px; }
        .container { background: #fff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1e3a5f, #ff6b35); padding: 30px; text-align: center; color: #fff; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .order-info { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .total-row { font-weight: bold; font-size: 16px; padding: 12px 0; }
        .footer { background: #1e3a5f; color: #ccc; text-align: center; padding: 20px; font-size: 13px; }
        .badge { display: inline-block; background: #ff6b35; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎉 Order Confirmed!</h1>
        <p>Thank you for your purchase, {{ $order->customer->name }}</p>
    </div>
    <div class="body">
        <p>Your order has been placed successfully. We'll notify you when it ships.</p>
        <div class="order-info">
            <p><strong>Order #:</strong> {{ $order->id }}</p>
            <p><strong>Status:</strong> <span class="badge">Pending</span></p>
            <p><strong>Payment:</strong> {{ strtoupper($order->payment_method) }}</p>
            <p><strong>Date:</strong> {{ $order->created_at->format('M d, Y') }}</p>
        </div>

        <h3>Items Ordered</h3>
        @foreach($order->items as $item)
        <div class="item">
            <span>{{ $item->name }} × {{ $item->quantity }}</span>
            <span>PKR {{ number_format($item->total, 2) }}</span>
        </div>
        @endforeach
        <div class="item total-row">
            <span>Grand Total</span>
            <span>PKR {{ number_format($order->grand_total, 2) }}</span>
        </div>

        <h3>Shipping To</h3>
        <p>
            {{ $order->shipping_address['full_name'] ?? '' }}<br>
            {{ $order->shipping_address['address_line_1'] ?? '' }}, {{ $order->shipping_address['city'] ?? '' }}<br>
            {{ $order->shipping_address['country'] ?? '' }} {{ $order->shipping_address['postal_code'] ?? '' }}
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} ShopPro. All rights reserved.
    </div>
</div>
</body>
</html>
