@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Réception de facture - Commande #{{ $order->id }}</h1>

    <form action="{{ route('supplier-orders.storeInvoiceReception', [$supplier, $order]) }}" method="POST">
        @csrf

        <table class="w-full border border-gray-300 mb-4">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2 border">Produit</th>
                    <th class="p-2 border">Quantité reçue</th>
                    <th class="p-2 border">Prix attendu</th>
                    <th class="p-2 border">Prix facturé</th>
                    <th class="p-2 border">Mettre à jour prix référence</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->products as $product)
                <tr class="border-b">
                    <td class="p-2 border">{{ $product->name[app()->getLocale()] ?? reset($product->name) }} @if($product->brand) ({{ $product->brand->name }}) @endif</td>
                    <td class="p-2 border">{{ $product->pivot->quantity_received ?? $product->pivot->quantity_ordered }}</td>
                    <td class="p-2 border">{{ number_format($product->pivot->purchase_price, 2) }} €</td>
                    <td class="p-2 border">
                        <input type="number" step="0.01" name="products[{{ $product->id }}][price_invoiced]" 
                               value="{{ old('products.'.$product->id.'.price_invoiced', $product->pivot->purchase_price) }}" 
                               class="border p-1 w-full" required>
                    </td>
                    <td class="p-2 border text-center">
                        <input type="checkbox" name="update_reference_price[{{ $product->id }}]" value="1">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Enregistrer la réception de facture
            </button>
        </div>
    </form>
</div>
@endsection
