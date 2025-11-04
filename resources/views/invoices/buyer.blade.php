<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
    </style>
</head>
<body>
    <h1>Invoice #{{ $invoice->id }}</h1>
    <p><strong>Reseller:</strong> {{ $delivery->reseller->name }}</p>
    <p><strong>Date:</strong> {{ $invoice->created_at->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->products as $product)
                <tr>
                    <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                    <td>{{ $product->pivot->quantity }}</td>
                    <td>{{ number_format($product->pivot->unit_price, 2) }} $</td>
                    <td>{{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }} $</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" align="right"><strong>Shipping</strong></td>
                <td>{{ number_format($delivery->shipping_cost, 2) }} $</td>
            </tr>
            <tr>
                <td colspan="3" align="right"><strong>Total</strong></td>
                <td><strong>{{ number_format($invoice->total_amount, 2) }} $</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
