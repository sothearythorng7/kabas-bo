<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture Sales Report #{{ $report->id }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <h2>Facture Sales Report #{{ $report->id }}</h2>
    <p><strong>Revendeur:</strong> {{ $reseller->name }}</p>
    <p><strong>Date:</strong> {{ $report->created_at->format('d/m/Y') }}</p>

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
            @foreach($report->items as $item)
                <tr>
                    <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                    <td>{{ $item->quantity_sold }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }} €</td>
                    <td class="text-right">{{ number_format($item->quantity_sold * $item->unit_price, 2, ',', ' ') }} €</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="total text-right">Total</td>
                <td class="total text-right">{{ number_format($totalValue, 2, ',', ' ') }} €</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
