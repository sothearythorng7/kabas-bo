@extends('reception.layouts.app')

@section('title', __('messages.reception.create_transfer'))

@section('styles')
<style>
    .store-select {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 20px;
    }
    .store-select label {
        font-weight: 600;
        font-size: 14px;
        color: var(--text-light);
    }
    .store-select select {
        width: 100%;
        height: 48px;
        padding: 0 16px;
        border: 2px solid var(--border);
        border-radius: 12px;
        font-size: 16px;
        background: var(--white);
        outline: none;
    }
    .store-select select:focus {
        border-color: var(--primary);
    }
    .products-section {
        display: none;
    }
    .products-section.visible {
        display: block;
    }
    .selected-products {
        margin-top: 16px;
    }
    .selected-product {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: var(--white);
        border-radius: 12px;
        margin-bottom: 8px;
    }
    .selected-product-info {
        flex: 1;
    }
    .selected-product-name {
        font-weight: 600;
        font-size: 14px;
    }
    .selected-product-meta {
        font-size: 12px;
        color: var(--text-light);
    }
    .remove-btn {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 8px;
        background: var(--danger);
        color: white;
        font-size: 18px;
        cursor: pointer;
    }
</style>
@endsection

@section('content')
<div class="header">
    <a href="{{ route('reception.transfers') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ __('messages.reception.back') }}
    </a>
    <div class="header-title">{{ __('messages.reception.create_transfer') }}</div>
    <div style="width: 60px;"></div>
</div>

<div class="container has-sticky-bottom">
    <!-- Step 1: Select stores -->
    <div class="store-select">
        <div>
            <label>{{ __('messages.reception.source_store') }}</label>
            <select id="fromStore">
                <option value="">{{ __('messages.reception.select_store') }}</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>{{ __('messages.reception.destination_store') }}</label>
            <select id="toStore">
                <option value="">{{ __('messages.reception.select_store') }}</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Step 2: Products section (hidden until stores selected) -->
    <div id="productsSection" class="products-section">
        <div class="search-box">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" id="searchProduct" placeholder="{{ __('messages.reception.search_product') }}">
        </div>

        <div id="productsList" style="max-height: 300px; overflow-y: auto;">
            <!-- Products will be loaded here via AJAX -->
        </div>

        <div class="selected-products">
            <div class="text-sm text-muted mb-4">{{ __('messages.reception.selected_products') }}</div>
            <div id="selectedProductsList">
                <!-- Selected products will appear here -->
            </div>
        </div>
    </div>

    <!-- Note field -->
    <div style="margin-top: 16px;">
        <label style="font-weight: 600; font-size: 14px; color: var(--text-light);">{{ __('messages.reception.note') }}</label>
        <textarea id="transferNote" class="input" style="height: 80px; padding: 12px; resize: none;" placeholder="{{ __('messages.reception.note_placeholder') }}"></textarea>
    </div>
</div>

<div class="sticky-bottom">
    <div class="flex gap-4" style="flex-direction: column;">
        <div class="flex justify-between items-center" style="margin-bottom: 8px;">
            <span class="text-muted">{{ __('messages.reception.items_to_transfer') }}:</span>
            <span id="itemsCount" class="font-bold">0</span>
        </div>
        <button type="button" class="btn btn-success" id="saveTransferBtn" disabled onclick="saveTransfer()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ __('messages.reception.save_transfer') }}
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let products = [];
    let selectedProducts = {};

    const fromStoreSelect = document.getElementById('fromStore');
    const toStoreSelect = document.getElementById('toStore');
    const productsSection = document.getElementById('productsSection');
    const productsList = document.getElementById('productsList');
    const selectedProductsList = document.getElementById('selectedProductsList');

    // When source store changes, load products
    fromStoreSelect.addEventListener('change', function() {
        checkStoresAndLoadProducts();
    });

    toStoreSelect.addEventListener('change', function() {
        checkStoresAndLoadProducts();
    });

    function checkStoresAndLoadProducts() {
        const fromStoreId = fromStoreSelect.value;
        const toStoreId = toStoreSelect.value;

        if (fromStoreId && toStoreId && fromStoreId !== toStoreId) {
            productsSection.classList.add('visible');
            loadProducts(fromStoreId);
        } else {
            productsSection.classList.remove('visible');
        }
    }

    async function loadProducts(fromStoreId) {
        productsList.innerHTML = '<div class="loading"><div class="spinner"></div></div>';

        try {
            const response = await fetch('{{ route("reception.transfers.products") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ from_store_id: fromStoreId })
            });

            const data = await response.json();
            products = data.products || [];
            renderProducts();
        } catch (error) {
            console.error('Error loading products:', error);
            productsList.innerHTML = '<div class="empty-state"><div>{{ __("messages.reception.error_loading_products") }}</div></div>';
        }
    }

    function renderProducts() {
        if (products.length === 0) {
            productsList.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📦</div><div>{{ __("messages.reception.no_products_with_stock") }}</div></div>';
            return;
        }

        let html = '';
        products.forEach(product => {
            const isSelected = selectedProducts[product.id] !== undefined;
            html += `
                <div class="product-item product-card" data-product-id="${product.id}" data-name="${product.name.toLowerCase()}" style="cursor: pointer; ${isSelected ? 'opacity: 0.5;' : ''}" onclick="selectProduct(${product.id})">
                    <img src="${product.thumbnail}" alt="" class="product-image">
                    <div class="product-info">
                        <div class="product-name">${product.name}</div>
                        <div class="product-meta">${product.brand || ''} - {{ __('messages.reception.stock') }}: ${product.stock}</div>
                    </div>
                </div>
            `;
        });
        productsList.innerHTML = html;
    }

    function selectProduct(productId) {
        const product = products.find(p => p.id === productId);
        if (!product || selectedProducts[productId]) return;

        selectedProducts[productId] = {
            product: product,
            quantity: 1
        };

        renderSelectedProducts();
        renderProducts();
        updateCount();
    }

    function renderSelectedProducts() {
        if (Object.keys(selectedProducts).length === 0) {
            selectedProductsList.innerHTML = '<div class="text-muted text-sm">{{ __("messages.reception.no_products_selected") }}</div>';
            return;
        }

        let html = '';
        for (const [productId, item] of Object.entries(selectedProducts)) {
            html += `
                <div class="selected-product">
                    <div class="selected-product-info">
                        <div class="selected-product-name">${item.product.name}</div>
                        <div class="selected-product-meta">{{ __('messages.reception.max') }}: ${item.product.stock}</div>
                    </div>
                    <div class="quantity-control">
                        <button type="button" class="quantity-btn" onclick="changeQty(${productId}, -1)">-</button>
                        <input type="number"
                               class="quantity-input"
                               id="qty-${productId}"
                               value="${item.quantity}"
                               min="1"
                               max="${item.product.stock}"
                               onchange="updateQty(${productId}, this.value)"
                               inputmode="numeric">
                        <button type="button" class="quantity-btn" onclick="changeQty(${productId}, 1)">+</button>
                    </div>
                    <button type="button" class="remove-btn" onclick="removeProduct(${productId})">×</button>
                </div>
            `;
        }
        selectedProductsList.innerHTML = html;
    }

    function changeQty(productId, delta) {
        const item = selectedProducts[productId];
        if (!item) return;

        const newQty = Math.max(1, Math.min(item.product.stock, item.quantity + delta));
        item.quantity = newQty;
        document.getElementById('qty-' + productId).value = newQty;
        updateCount();
    }

    function updateQty(productId, value) {
        const item = selectedProducts[productId];
        if (!item) return;

        const qty = Math.max(1, Math.min(item.product.stock, parseInt(value) || 1));
        item.quantity = qty;
        document.getElementById('qty-' + productId).value = qty;
        updateCount();
    }

    function removeProduct(productId) {
        delete selectedProducts[productId];
        renderSelectedProducts();
        renderProducts();
        updateCount();
    }

    function updateCount() {
        const count = Object.keys(selectedProducts).length;
        document.getElementById('itemsCount').textContent = count;
        document.getElementById('saveTransferBtn').disabled = count === 0;
    }

    // Search products
    document.getElementById('searchProduct').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const name = card.dataset.name;
            card.style.display = name.includes(search) ? 'flex' : 'none';
        });
    });

    async function saveTransfer() {
        const btn = document.getElementById('saveTransferBtn');
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner" style="width: 24px; height: 24px; border-width: 3px;"></div> {{ __("messages.reception.saving") }}...';

        const items = [];
        for (const [productId, item] of Object.entries(selectedProducts)) {
            items.push({
                product_id: parseInt(productId),
                quantity: item.quantity
            });
        }

        if (items.length === 0) {
            alert('{{ __("messages.reception.select_at_least_one") }}');
            btn.disabled = false;
            btn.innerHTML = '{{ __("messages.reception.save_transfer") }}';
            return;
        }

        try {
            const response = await fetch('{{ route("reception.transfers.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    from_store_id: fromStoreSelect.value,
                    to_store_id: toStoreSelect.value,
                    items: items,
                    note: document.getElementById('transferNote').value
                })
            });

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            const data = await response.json();
            if (data.error) {
                alert(data.error);
                btn.disabled = false;
                btn.innerHTML = '{{ __("messages.reception.save_transfer") }}';
            } else {
                window.location.href = '{{ route("reception.home") }}';
            }
        } catch (error) {
            console.error('Save error:', error);
            window.location.href = '{{ route("reception.home") }}';
        }
    }
</script>
@endsection
