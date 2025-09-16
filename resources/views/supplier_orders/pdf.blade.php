<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Commande #{{ $order->id }}</title>
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
    <h1>Commande #{{ $order->id }} - {{ $supplier->name }}</h1>

    <p><strong>Status:</strong>
        @if($order->status === 'pending')
            <span class="badge badge-warning">En attente</span>
        @elseif($order->status === 'waiting_reception')
            <span class="badge badge-info">En attente de réception</span>
        @else
            <span class="badge badge-success">Réceptionnée</span>
        @endif
    </p>

    <p><strong>Date création:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>EAN</th>
                <th>Produit</th>
                <th>Marque</th>
                <th>Prix d'achat</th>
                <th>Prix de vente</th>
                <th>Qté commandée</th>
                <th>Qté reçue</th>
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
