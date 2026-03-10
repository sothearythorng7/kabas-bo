<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmed - {{ $order->order_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f6fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .pay-container { max-width: 520px; margin: 40px auto; padding: 0 16px; }
        .pay-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden; text-align: center; }
        .pay-header { background: linear-gradient(135deg, #0f5132 0%, #198754 100%); color: #fff; padding: 32px 24px; }
        .checkmark { font-size: 48px; margin-bottom: 12px; }
        .pay-header h1 { font-size: 22px; margin: 0 0 4px; font-weight: 600; }
        .pay-body { padding: 24px; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .powered { text-align: center; padding: 16px; font-size: 12px; color: #999; }
    </style>
</head>
<body>

<div class="pay-container">
    <div class="pay-card">
        <div class="pay-header">
            <div class="checkmark">&#10003;</div>
            <h1>Payment Confirmed</h1>
            <p style="margin:4px 0 0; opacity:0.8; font-size:14px;">Thank you for your payment</p>
        </div>

        <div class="pay-body">
            <div class="detail-row">
                <span>Order</span>
                <strong>{{ $order->order_number }}</strong>
            </div>
            <div class="detail-row">
                <span>Amount</span>
                <strong>${{ number_format($order->total, 2) }} USD</strong>
            </div>
            <div class="detail-row">
                <span>Status</span>
                <span class="badge bg-success">Paid</span>
            </div>

            <p class="text-muted mt-3" style="font-size: 13px;">
                A confirmation email has been sent to <strong>{{ $order->contact_email }}</strong>.
                If you have any questions, please contact us.
            </p>
        </div>
    </div>
    <div class="powered">Kabas Concept Store</div>
</div>
</body>
</html>
