<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Delivered</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f8; margin: 0; padding: 20px; }
        .container { background: #fff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1e3a5f, #2ecc71); padding: 30px; text-align: center; color: #fff; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .cta { background: #ff6b35; color: #fff; display: inline-block; padding: 12px 24px; border-radius: 6px; text-decoration: none; margin: 20px 0; }
        .footer { background: #1e3a5f; color: #ccc; text-align: center; padding: 20px; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>✅ Order Delivered!</h1>
        <p>Enjoy your purchase, {{ $order->customer->name }}!</p>
    </div>
    <div class="body">
        <p>Your order <strong>#{{ $order->id }}</strong> has been delivered. We hope you love it!</p>
        <p>Would you like to leave a review? Your feedback helps other shoppers make better decisions.</p>
        <p>You can also request a refund within 14 days if you're not satisfied.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} ShopPro. All rights reserved.
    </div>
</div>
</body>
</html>
