<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.invoice.shop_title') }} #{{ $invoice->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h1>{{ __('messages.invoice.shop_title') }} #{{ $invoice->id }}</h1>
    <p><strong>{{ __('messages.invoice.shop_name') }} :</strong> {{ $delivery->store->name }}</p>
    <p><strong>{{ __('messages.common.date') }} :</strong> {{ $delivery->delivered_at ?? $delivery->created_at->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ __('messages.product.name') }}</th>
                <th>{{ __('messages.resellers.quantity') }}</th>
                <th>{{ __('messages.resellers.unit_price') }}</th>
                <th>{{ __('messages.common.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->products as $product)
                <tr>
                    <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                    <td>{{ $product->pivot->quantity }}</td>
                    <td>{{ number_format($product->pivot->unit_price, 2) }}</td>
                    <td>{{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><strong>{{ __('messages.resellers.shipping_cost') }} :</strong> {{ number_format($delivery->shipping_cost ?? 0, 2) }} $</p>
    <p><strong>{{ __('messages.common.total') }} :</strong> {{ number_format($invoice->total_amount, 2) }} $</p>
</body>
</html>
