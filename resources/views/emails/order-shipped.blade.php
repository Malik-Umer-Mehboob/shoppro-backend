<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Shipped</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f8; margin: 0; padding: 20px; }
        .container { background: #fff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1e3a5f, #ff6b35); padding: 30px; text-align: center; color: #fff; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .info-box { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .tracking { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 16px; margin: 20px 0; }
        .footer { background: #1e3a5f; color: #ccc; text-align: center; padding: 20px; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🚚 Your Order is On the Way!</h1>
        <p>Good news, {{ $order->customer->name }}!</p>
    </div>
    <div class="body">
        <p>Your order <strong>#{{ $order->id }}</strong> has been shipped and is on its way to you.</p>
        
        @if($order->tracking_number)
        <div class="tracking">
            <strong>📦 Tracking Number:</strong> {{ $order->tracking_number }}<br>
            <small>Use this number to track your shipment.</small>
        </div>
        @endif

        <div class="info-box">
            <p><strong>Shipping To:</strong><br>
            {{ $order->shipping_address['full_name'] ?? '' }}<br>
            {{ $order->shipping_address['address_line_1'] ?? '' }}, {{ $order->shipping_address['city'] ?? '' }}</p>
        </div>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} ShopPro. All rights reserved.
    </div>
</div>
</body>
</html>
