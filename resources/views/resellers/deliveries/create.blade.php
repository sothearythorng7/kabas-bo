@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.resellers.deliveries') }} - {{ __('messages.btn.create') }} : {{ $reseller->name }}</h1>

    <form method="POST" action="{{ route('resellers.deliveries.store', $reseller) }}">
        @csrf

        <!-- Recherche produit -->
        <div class="mb-3">
            <input type="text" id="productFilter" class="form-control" placeholder="{{ __('messages.stock_value.search_placeholder') }}">
        </div>

        <!-- Tableau produits -->
        <table class="table table-bordered" id="productTable">
            <thead>
                <tr>
                    <th>{{ __('messages.stock_value.ean') }}</th>
                    <th>{{ __('messages.product.name') }}</th>
                    <th>{{ __('messages.resellers.quantity') }}</th>
                    <th>{{ __('messages.product.price_btob') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td>{{ $product->ean }}</td>
                        <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                        <td>
                            <input type="number" name="products[{{ $product->id }}][quantity]" class="form-control form-control-sm" min="0" value="{{ old("products.{$product->id}.quantity") }}">
                        </td>
                        <td>
                            <input type="number" step="0.01" 
                                name="products[{{ $product->id }}][unit_price]" 
                                class="form-control form-control-sm" 
                                value="{{ old("products.{$product->id}.unit_price", $product->price_btob ?? $product->price) }}">
                        </td>
                        <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
                    </tr>
                @endforeach
            </tbody>
        </table>

        <button class="btn btn-success">{{ __('messages.btn.create') }}</button>
        <a href="{{ route('resellers.show', $reseller) }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('productFilter').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#productTable tbody tr').forEach(tr => {
        const ean = tr.cells[0].textContent.toLowerCase();
        const name = tr.cells[1].textContent.toLowerCase();
        tr.style.display = (ean.includes(filter) || name.includes(filter)) ? '' : 'none';
    });
});
</script>
@endpush
