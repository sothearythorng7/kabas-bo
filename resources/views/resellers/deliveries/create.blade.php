@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.resellers.deliveries') }} - {{ __('messages.btn.create') }} : {{ $reseller->name }}</h1>

    <form method="POST" action="{{ route('resellers.deliveries.store', $reseller->id) }}">
        @csrf

        <!-- Recherche produit -->
        <div class="mb-3">
            <input type="text" id="productFilter" class="form-control" placeholder="{{ __('messages.stock_value.search_placeholder') }}">
        </div>

        <!-- Desktop -->
        <div class="d-none d-md-block">
            <table class="table table-striped" id="productTable">
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
                                <input type="number"
                                    name="products[{{ $product->id }}][quantity]"
                                    min="0"
                                    max="{{ $product->available_stock }}"
                                    value="{{ old("products.$product->id.quantity", 0) }}"
                                    class="quantity-input"
                                    data-product-id="{{ $product->id }}">
                                <small>Stock disponible: {{ $product->available_stock }}</small>
                            </td>
                            <td>
                                <input type="number" step="0.01" 
                                       name="products[{{ $product->id }}][unit_price]" 
                                       class="form-control form-control-sm price-input" 
                                       data-product-id="{{ $product->id }}"
                                       value="{{ old("products.{$product->id}.unit_price", $product->price_btob ?? $product->price) }}">
                            </td>
                            <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile -->
        <div class="d-md-none">
            <div class="row">
                @foreach($products as $product)
                    <div class="col-12 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <h5 class="card-title mb-1">{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>
                                <p class="mb-1"><strong>EAN:</strong> {{ $product->ean }}</p>
                                
                                <div class="mb-2">
                                    <label class="form-label mb-0">{{ __('messages.resellers.quantity') }}</label>
                                    <input type="number" 
                                           name="products[{{ $product->id }}][quantity]" 
                                           class="form-control form-control-sm quantity-input"
                                           data-product-id="{{ $product->id }}"
                                           min="0"
                                           max="{{ $product->available_stock }}"
                                           value="{{ old("products.{$product->id}.quantity", 0) }}">
                                    <small>Stock disponible: {{ $product->available_stock }}</small>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label mb-0">{{ __('messages.product.price_btob') }}</label>
                                    <input type="number" step="0.01" 
                                           name="products[{{ $product->id }}][unit_price]" 
                                           class="form-control form-control-sm price-input" 
                                           data-product-id="{{ $product->id }}"
                                           value="{{ old("products.{$product->id}.unit_price", $product->price_btob ?? $product->price) }}">
                                </div>

                                <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <button class="btn btn-success">{{ __('messages.btn.create') }}</button>
        <a href="{{ route('resellers.show', $reseller->id) }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
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

// Synchronisation des champs desktop/mobile
document.querySelectorAll('.quantity-input, .price-input').forEach(input => {
    input.addEventListener('input', function() {
        const productId = this.dataset.productId;
        const type = this.classList.contains('quantity-input') ? 'quantity' : 'unit_price';
        document.querySelectorAll(`.${type}-input[data-product-id="${productId}"]`).forEach(other => {
            if (other !== this) other.value = this.value;
        });
    });
});
</script>
@endpush
