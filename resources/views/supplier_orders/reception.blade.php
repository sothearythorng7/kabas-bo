@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Réception commande #{{ $order->id }} - {{ $supplier->name }}</h1>

    <form action="{{ route('supplier-orders.storeReception', [$supplier, $order]) }}" method="POST">
        @csrf

        {{-- Table desktop --}}
        <div class="d-none d-md-block">
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
                                value="{{ $p->pivot->quantity_received ?? $p->pivot->quantity_ordered }}" 
                                class="form-control form-control-sm" style="max-width:100px;">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Version mobile --}}
        <div class="d-md-none">
            <div class="row">
                @foreach($order->products as $p)
                <div class="col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <h5 class="card-title">{{ $p->name['en'] ?? reset($p->name) }}</h5>
                            <p><strong>EAN:</strong> {{ $p->ean }}</p>
                            <p><strong>Brand:</strong> {{ $p->brand?->name ?? '-' }}</p>
                            <p><strong>Qté commandée:</strong> {{ $p->pivot->quantity_ordered }}</p>
                            <div class="mb-1">
                                <label class="form-label">{{ __('messages.supplier_order.received_quantity') }}</label>
                                <input type="number" min="0" name="products[{{ $p->id }}]" 
                                    value="{{ $p->pivot->quantity_received ?? $p->pivot->quantity_ordered }}" 
                                    class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

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
