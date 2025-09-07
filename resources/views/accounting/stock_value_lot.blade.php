<h5>Lots pour {{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>

@if($lots->isEmpty())
    <p>Aucun lot disponible.</p>
@else
<table class="table table-sm table-striped">
    <thead>
        <tr>
            <th>Magasin</th>
            <th>Quantité restante</th>
            <th>Prix d'achat unitaire</th>
            <th>Valeur</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lots as $lot)
        <tr>
            <td>{{ $lot->store->name ?? '-' }}</td>
            <td>{{ $lot->quantity_remaining }}</td>
            <td>{{ number_format($lot->purchase_price, 2) }} €</td>
            <td>{{ number_format($lot->quantity_remaining * $lot->purchase_price, 2) }} €</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
