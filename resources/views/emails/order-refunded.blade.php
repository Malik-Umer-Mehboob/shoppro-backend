<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Refund Processed</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f8; margin: 0; padding: 20px; }
        .container { background: #fff; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1e3a5f, #e74c3c); padding: 30px; text-align: center; color: #fff; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .info-box { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .footer { background: #1e3a5f; color: #ccc; text-align: center; padding: 20px; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>💸 Refund Processed</h1>
        <p>Hi {{ $order->customer->name }},</p>
    </div>
    <div class="body">
        <p>Your refund for order <strong>#{{ $order->id }}</strong> has been processed successfully.</p>
        <div class="info-box">
            <p><strong>Refund Amount:</strong> PKR {{ number_format($order->grand_total, 2) }}</p>
            <p><strong>Payment Method:</strong> {{ strtoupper($order->payment_method) }}</p>
        </div>
        <p>Please allow 3–7 business days for the refund to reflect in your account.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} ShopPro. All rights reserved.
    </div>
</div>
</body>
</html>
