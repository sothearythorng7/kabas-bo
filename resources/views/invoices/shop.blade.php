<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture Shop #{{ $invoice->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Facture Shop #{{ $invoice->id }}</h1>
    <p><strong>Nom du shop :</strong> {{ $delivery->store->name }}</p>
    <p><strong>Date :</strong> {{ $delivery->delivered_at ?? $delivery->created_at->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Total</th>
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

    <p><strong>Frais de livraison :</strong> {{ number_format($delivery->shipping_cost ?? 0, 2) }} $</p>
    <p><strong>Total :</strong> {{ number_format($invoice->total_amount, 2) }} $</p>
</body>
</html>
