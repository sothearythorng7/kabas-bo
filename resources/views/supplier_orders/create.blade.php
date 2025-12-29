@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_order.create_title') }} - {{ $supplier->name }}</h1>

    {{-- Version desktop --}}
    <div class="d-none d-md-block">
        <form action="{{ route('supplier-orders.store', $supplier) }}" method="POST">
            @csrf

            <div class="row mb-3">
                <!-- Menu déroulant pour la destination -->
                <div class="col-md-4">
                    <label for="destination_store_id" class="form-label">{{ __('messages.supplier_order.destination_store') }}</label>
                    <select name="destination_store_id" id="destination_store_id" class="form-control" required>
                        <option value="">{{ __('messages.supplier_order.select_destination') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->type }})</option>
                        @endforeach
                    </select>
                    @error('destination_store_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Champ de recherche -->
                <div class="col-md-4">
                    <label for="searchProduct" class="form-label">{{ __('messages.common.search') }}</label>
                    <input type="text" id="searchProduct" class="form-control" placeholder="{{ __('messages.supplier_order.search_placeholder') }}">
                </div>

                <!-- Compteur produits sélectionnés -->
                <div class="col-md-4 d-flex align-items-end">
                    <div class="alert alert-info mb-0 py-2 w-100">
                        <strong id="selectedCount">0</strong> {{ __('messages.supplier_order.products_with_quantity') }}
                    </div>
                </div>
            </div>

            <table class="table table-striped" id="productsTable">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>Brand</th>
                        <th>{{ __('messages.supplier.stock') }}</th>
                        <th>{{ __('messages.product.purchase_price') }}</th>
                        <th>{{ __('messages.product.price') }}</th>
                        <th>{{ __('messages.supplier_order.quantity') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    @php
                        $productName = $product->name[app()->getLocale()] ?? reset($product->name);
                    @endphp
                    <tr class="product-row"
                        data-product-id="{{ $product->id }}"
                        data-name="{{ strtolower($productName) }}"
                        data-ean="{{ strtolower($product->ean ?? '') }}"
                        data-brand="{{ strtolower($product->brand?->name ?? '') }}">
                        <td>{{ $product->ean }}</td>
                        <td>{{ $productName }}</td>
                        <td>{{ $product->brand?->name ?? '-' }}</td>
                        <td class="stock-cell">
                            <span class="badge bg-secondary stock-badge" data-product-id="{{ $product->id }}">-</span>
                        </td>
                        <td>{{ number_format($product->pivot->purchase_price ?? 0, 2) }}</td>
                        <td>{{ number_format($product->price, 2) }}</td>
                        <td>
                            <input type="number" min="0" name="products[{{ $product->id }}][quantity]" value="0" class="form-control form-control-sm quantity-input" style="max-width:100px;">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.supplier_order.create') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Version mobile --}}
    <div class="d-md-none">
        <form action="{{ route('supplier-orders.store', $supplier) }}" method="POST">
            @csrf

            <!-- Menu déroulant pour la destination -->
            <div class="mb-3">
                <label for="destination_store_id_mobile" class="form-label">{{ __('messages.supplier_order.destination_store') }}</label>
                <select name="destination_store_id" id="destination_store_id_mobile" class="form-control store-select" required>
                    <option value="">{{ __('messages.supplier_order.select_destination') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->type }})</option>
                    @endforeach
                </select>
                @error('destination_store_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Champ de recherche mobile -->
            <div class="mb-3">
                <input type="text" id="searchProductMobile" class="form-control" placeholder="{{ __('messages.supplier_order.search_placeholder') }}">
            </div>

            <div class="row" id="productCardsContainer">
                @foreach($products as $product)
                @php
                    $productName = $product->name[app()->getLocale()] ?? reset($product->name);
                @endphp
                <div class="col-12 mb-3 product-card"
                     data-product-id="{{ $product->id }}"
                     data-name="{{ strtolower($productName) }}"
                     data-ean="{{ strtolower($product->ean ?? '') }}"
                     data-brand="{{ strtolower($product->brand?->name ?? '') }}">
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <h5 class="card-title">{{ $productName }}</h5>
                            <p class="mb-1"><strong>EAN:</strong> {{ $product->ean }}</p>
                            <p class="mb-1"><strong>Brand:</strong> {{ $product->brand?->name ?? '-' }}</p>
                            <p class="mb-1">
                                <strong>{{ __('messages.supplier.stock') }}:</strong>
                                <span class="badge bg-secondary stock-badge" data-product-id="{{ $product->id }}">-</span>
                            </p>
                            <p class="mb-1"><strong>{{ __('messages.product.purchase_price') }}:</strong> {{ number_format($product->pivot->purchase_price ?? 0, 2) }}</p>
                            <p class="mb-1"><strong>{{ __('messages.product.price') }}:</strong> {{ number_format($product->price, 2) }}</p>
                            <div class="mb-1">
                                <label class="form-label">{{ __('messages.supplier_order.quantity') }}</label>
                                <input type="number" min="0" name="products[{{ $product->id }}][quantity]" value="0" class="form-control form-control-sm quantity-input">
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.supplier_order.create') }}
                </button>
            </div>
        </form>
    </div>

    <div class="mt-3">
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierId = {{ $supplier->id }};
    const stockUrl = '{{ url("suppliers/{$supplier->id}/orders/stock") }}';

    // Store selection handlers
    const storeSelects = document.querySelectorAll('#destination_store_id, #destination_store_id_mobile');
    storeSelects.forEach(select => {
        select.addEventListener('change', function() {
            const storeId = this.value;
            // Sync both selects
            storeSelects.forEach(s => s.value = storeId);

            if (storeId) {
                loadStock(storeId);
            } else {
                resetStock();
            }
        });
    });

    function loadStock(storeId) {
        fetch(`${stockUrl}/${storeId}`)
            .then(response => response.json())
            .then(stocks => {
                document.querySelectorAll('.stock-badge').forEach(badge => {
                    const productId = badge.dataset.productId;
                    const stock = stocks[productId] || 0;
                    badge.textContent = stock;
                    badge.className = stock > 0 ? 'badge bg-info stock-badge' : 'badge bg-secondary stock-badge';
                });
            })
            .catch(error => {
                console.error('Error loading stock:', error);
            });
    }

    function resetStock() {
        document.querySelectorAll('.stock-badge').forEach(badge => {
            badge.textContent = '-';
            badge.className = 'badge bg-secondary stock-badge';
        });
    }

    // Search filter - Desktop
    const searchInput = document.getElementById('searchProduct');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const search = this.value.toLowerCase();
            filterProducts(search, '.product-row');
        });
    }

    // Search filter - Mobile
    const searchInputMobile = document.getElementById('searchProductMobile');
    if (searchInputMobile) {
        searchInputMobile.addEventListener('input', function() {
            const search = this.value.toLowerCase();
            filterProducts(search, '.product-card');
        });
    }

    function filterProducts(search, selector) {
        document.querySelectorAll(selector).forEach(item => {
            const name = item.dataset.name || '';
            const ean = item.dataset.ean || '';
            const brand = item.dataset.brand || '';

            const matches = name.includes(search) || ean.includes(search) || brand.includes(search);
            item.style.display = matches ? '' : 'none';
        });
    }

    // Count products with quantity
    function updateSelectedCount() {
        let count = 0;
        document.querySelectorAll('.quantity-input').forEach(input => {
            if (parseInt(input.value) > 0) {
                count++;
            }
        });
        const countEl = document.getElementById('selectedCount');
        if (countEl) {
            countEl.textContent = count;
        }
    }

    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', updateSelectedCount);
        input.addEventListener('input', updateSelectedCount);
    });

    // Initial count
    updateSelectedCount();
});
</script>
@endpush
