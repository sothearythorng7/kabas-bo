@extends('reception.layouts.app')

@section('title', 'Refill - ' . $supplier->name)

@section('content')
<div class="header">
    <a href="{{ route('reception.refill') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back
    </a>
    <div class="header-title">{{ $supplier->name }}</div>
    <div style="width: 60px;"></div>
</div>

<div class="container has-sticky-bottom">
    <div class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" id="searchProduct" placeholder="Search product...">
    </div>

    <div class="text-sm text-muted mb-4">
        Enter quantities for products you're receiving
    </div>

    @if($products->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">No products</div>
            <div>This supplier has no products</div>
        </div>
    @else
        <div id="productsList">
            @foreach($products as $product)
                @php
                    $productName = $product->name['en'] ?? $product->name['fr'] ?? 'Product';
                    $thumbnail = $product->images->first() ? '/storage/' . $product->images->first()->path : '/images/placeholder.png';
                @endphp
                <div class="product-item product-card" data-product-id="{{ $product->id }}" data-name="{{ strtolower($productName) }}" style="flex-direction: column; align-items: stretch;">
                    <div class="product-name" style="margin-bottom: 8px; white-space: normal; line-height: 1.3;">{{ $productName }}</div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <img src="{{ $thumbnail }}" alt="" class="product-image">
                        <div class="product-info" style="flex: 1;">
                            <div class="product-meta">
                                @if($product->brand)
                                    {{ $product->brand->name }}
                                @endif
                            </div>
                        </div>
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn" onclick="changeQty({{ $product->id }}, -1)">-</button>
                            <input type="number"
                                   class="quantity-input refill-qty"
                                   id="qty-{{ $product->id }}"
                                   value="0"
                                   min="0"
                                   max="999"
                                   inputmode="numeric">
                            <button type="button" class="quantity-btn" onclick="changeQty({{ $product->id }}, 1)">+</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="sticky-bottom">
    <div class="flex gap-4" style="flex-direction: column;">
        <div class="flex justify-between items-center" style="margin-bottom: 8px;">
            <span class="text-muted">Items to add:</span>
            <span id="itemsCount" class="font-bold">0</span>
        </div>
        <button type="button" class="btn btn-success" id="saveRefillBtn" disabled onclick="saveRefill()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Save Refill
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const supplierId = {{ $supplier->id }};

    function changeQty(productId, delta) {
        const input = document.getElementById('qty-' + productId);
        let value = parseInt(input.value) || 0;
        value = Math.max(0, value + delta);
        input.value = value;
        updateCount();
    }

    function updateCount() {
        let count = 0;
        document.querySelectorAll('.refill-qty').forEach(input => {
            if (parseInt(input.value) > 0) {
                count++;
            }
        });

        document.getElementById('itemsCount').textContent = count;
        document.getElementById('saveRefillBtn').disabled = count === 0;
    }

    // Update count on input change
    document.querySelectorAll('.refill-qty').forEach(input => {
        input.addEventListener('change', updateCount);
        input.addEventListener('input', updateCount);
    });

    // Search
    document.getElementById('searchProduct')?.addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const name = card.dataset.name;
            card.style.display = name.includes(search) ? 'flex' : 'none';
        });
    });

    async function saveRefill() {
        const btn = document.getElementById('saveRefillBtn');
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner" style="width: 24px; height: 24px; border-width: 3px;"></div> Saving...';

        const items = [];
        document.querySelectorAll('.product-card').forEach(card => {
            const productId = card.dataset.productId;
            const qty = parseInt(document.getElementById('qty-' + productId).value) || 0;
            if (qty > 0) {
                items.push({
                    product_id: parseInt(productId),
                    quantity: qty
                });
            }
        });

        if (items.length === 0) {
            alert('Please enter at least one quantity');
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Save Refill';
            return;
        }

        try {
            const response = await fetch('{{ route("reception.refill.store", $supplier) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ items })
            });

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            const data = await response.json();

            if (data.error) {
                alert(data.error);
                btn.disabled = false;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Save Refill';
            } else {
                // Redirect to home
                window.location.href = '{{ route("reception.home") }}';
            }
        } catch (error) {
            console.error('Save error:', error);
            // If it's a redirect, follow it
            window.location.href = '{{ route("reception.home") }}';
        }
    }
</script>
@endsection
