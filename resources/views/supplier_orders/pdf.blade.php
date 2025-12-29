<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('messages.supplier_order.pdf_title') }} #{{ $order->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        h1 { font-size: 18px; }
        .badge { padding: 3px 6px; color: #fff; border-radius: 4px; }
        .badge-warning { background-color: #f0ad4e; }
        .badge-info { background-color: #5bc0de; }
        .badge-success { background-color: #5cb85c; }
    </style>
</head>
<body>
    <h1>{{ __('messages.supplier_order.pdf_title') }} #{{ $order->id }} - {{ $supplier->name }}</h1>

    <p><strong>{{ __('messages.common.status') }}:</strong>
        @if($order->status === 'pending')
            <span class="badge badge-warning">{{ __('messages.supplier_order.status.pending') }}</span>
        @elseif($order->status === 'waiting_reception')
            <span class="badge badge-info">{{ __('messages.supplier_order.status.waiting_reception') }}</span>
        @else
            <span class="badge badge-success">{{ __('messages.supplier_order.status.received') }}</span>
        @endif
    </p>

    <p><strong>{{ __('messages.supplier_order.created_at') }}:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>EAN</th>
                <th>{{ __('messages.product.name') }}</th>
                <th>{{ __('messages.product.brand') }}</th>
                <th>{{ __('messages.product.purchase_price') }}</th>
                <th>{{ __('messages.product.price') }}</th>
                <th>{{ __('messages.supplier_order.qty_ordered') }}</th>
                <th>{{ __('messages.supplier_order.qty_received') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->products as $product)
                <tr>
                    <td>{{ $product->ean }}</td>
                    <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                    <td>{{ $product->brand?->name ?? '-' }}</td>
                    <td>{{ number_format($product->pivot->purchase_price, 2) }}</td>
                    <td>{{ number_format($product->price, 2) }}</td>
                    <td>{{ $product->pivot->quantity_ordered }}</td>
                    <td>{{ $product->pivot->quantity_received ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
