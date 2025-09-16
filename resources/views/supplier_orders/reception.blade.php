@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Réception commande #{{ $order->id }} - {{ $supplier->name }}</h1>

    <form action="{{ route('supplier-orders.storeReception', [$supplier, $order]) }}" method="POST">
        @csrf

        {{-- Table unique --}}
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>EAN</th>
                    <th>{{ __('messages.product.name') }}</th>
                    <th>Brand</th>
                    <th>Qté commandée</th>
                    <th>{{ __('messages.supplier_order.received_quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->products as $p)
                <tr>
                    <td>{{ $p->ean }}</td>
                    <td>{{ $p->name['en'] ?? reset($p->name) }}</td>
                    <td>{{ $p->brand?->name ?? '-' }}</td>
                    <td>{{ $p->pivot->quantity_ordered }}</td>
                    <td>
                        <input type="number" min="0" name="products[{{ $p->id }}]" 
                            value="{{ $p->pivot->quantity_ordered }}" 
                            class="form-control form-control-sm" style="max-width:100px;">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check2-circle"></i> Valider réception
            </button>
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Annuler
            </a>
        </div>
    </form>
</div>
@endsection
