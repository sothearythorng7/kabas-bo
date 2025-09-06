<h2>Commande Fournisseur #{{ $order->id }}</h2>
<p>Fournisseur : {{ $supplier->name }}</p>
<p>Date : {{ $order->created_at->format('d/m/Y') }}</p>

<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Produit</th>
            <th>Prix achat</th>
            <th>Quantité</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @php $total = 0; @endphp
        @foreach($order->products as $p)
            @php $lineTotal = $p->pivot->purchase_price * $p->pivot->quantity_ordered; @endphp
            <tr>
                <td>{{ $p->name[app()->getLocale()] ?? $p->name['en'] ?? reset($p->name) }}</td>
                <td>{{ $p->pivot->purchase_price }}</td>
                <td>{{ $p->pivot->quantity_ordered }}</td>
                <td>{{ $lineTotal }}</td>
            </tr>
            @php $total += $lineTotal; @endphp
        @endforeach
    </tbody>
</table>

<h3>Total : {{ $total }}</h3>
