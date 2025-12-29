@extends('reception.layouts.app')

@section('title', 'Receive Order')

@section('content')
<div class="header">
    <a href="{{ route('reception.orders') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back
    </a>
    <div class="header-title">{{ $order->supplier->name }}</div>
    <div style="width: 60px;"></div>
</div>

<div class="container" style="padding-bottom: 160px;">
    <div class="card mb-4">
        <div class="flex justify-between">
            <div>
                <div class="text-sm text-muted">Order #{{ $order->id }}</div>
                <div class="font-bold">{{ $order->created_at->format('d/m/Y') }}</div>
            </div>
            <div class="text-right">
                <div class="text-sm text-muted">Destination</div>
                <div class="font-bold">{{ $order->destinationStore->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="text-sm text-muted mb-4">
        Enter received quantities for each product
    </div>

    @foreach($order->products as $product)
        @php
            $orderedQty = $product->pivot->quantity_ordered;
            $receivedQty = $receivedQuantities[$product->id] ?? 0;
            $productName = $product->name['en'] ?? $product->name['fr'] ?? 'Product';
            $thumbnail = $product->images->first() ? '/storage/' . $product->images->first()->path : '/images/placeholder.png';
        @endphp
        <div class="product-item" data-product-id="{{ $product->id }}" style="flex-direction: column; align-items: stretch;">
            <div class="product-name" style="margin-bottom: 8px; white-space: normal; line-height: 1.3;">{{ $productName }}</div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="{{ $thumbnail }}" alt="" class="product-image">
                <div class="product-info" style="flex: 1;">
                    <div class="product-meta">
                        Ordered: <strong>{{ $orderedQty }}</strong>
                        @if($receivedQty > 0)
                            &bull; <span style="color: var(--success);">Received: {{ $receivedQty }}</span>
                        @endif
                    </div>
                </div>
                <div class="quantity-control">
                    <button type="button" class="quantity-btn" onclick="changeQty({{ $product->id }}, -1)">-</button>
                    <input type="number"
                           class="quantity-input"
                           id="qty-{{ $product->id }}"
                           value="{{ $receivedQty }}"
                           min="0"
                           max="999"
                           onchange="saveQty({{ $product->id }})"
                           inputmode="numeric">
                    <button type="button" class="quantity-btn" onclick="changeQty({{ $product->id }}, 1)">+</button>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="sticky-bottom" style="display: flex; flex-direction: column; gap: 10px;">
    <button type="button" class="btn btn-secondary" id="savePartialBtn" onclick="savePartial()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
        </svg>
        Save & Continue Later
    </button>
    <form action="{{ route('reception.orders.finalize', $order) }}" method="POST" id="finalizeForm">
        @csrf
        <button type="submit" class="btn btn-success" id="finalizeBtn" style="width: 100%;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Finalize Reception
        </button>
    </form>
</div>
@endsection

@section('scripts')
<script>
    const orderId = {{ $order->id }};
    let saveTimeout = {};
    let saving = {};

    function changeQty(productId, delta) {
        const input = document.getElementById('qty-' + productId);
        let value = parseInt(input.value) || 0;
        value = Math.max(0, value + delta);
        input.value = value;
        saveQty(productId);
    }

    function saveQty(productId) {
        // Debounce saves
        if (saveTimeout[productId]) {
            clearTimeout(saveTimeout[productId]);
        }

        saveTimeout[productId] = setTimeout(() => {
            doSave(productId);
        }, 500);
    }

    async function doSave(productId) {
        if (saving[productId]) return;
        saving[productId] = true;

        const input = document.getElementById('qty-' + productId);
        const quantity = parseInt(input.value) || 0;

        // Visual feedback
        input.style.background = '#fef3c7';

        try {
            const response = await fetch('{{ route("reception.orders.receive-item", $order) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity_received: quantity
                })
            });

            const data = await response.json();

            if (data.success) {
                input.style.background = '#d1fae5';
                setTimeout(() => {
                    input.style.background = '';
                }, 1000);
            } else {
                input.style.background = '#fee2e2';
                alert(data.error || 'Error saving');
            }
        } catch (error) {
            input.style.background = '#fee2e2';
            console.error('Save error:', error);
        }

        saving[productId] = false;
    }

    // Confirm before finalizing
    document.getElementById('finalizeForm').addEventListener('submit', function(e) {
        if (!confirm('Finalize this reception? The order will move to the next step.')) {
            e.preventDefault();
        }
    });

    // Save partial and go back to list
    async function savePartial() {
        const btn = document.getElementById('savePartialBtn');
        btn.disabled = true;
        btn.innerHTML = '<span>Saving...</span>';

        // Wait for any pending saves to complete
        await new Promise(resolve => setTimeout(resolve, 600));

        // Check if any save is still in progress
        const stillSaving = Object.values(saving).some(v => v);
        if (stillSaving) {
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        // Redirect back to orders list with success message
        window.location.href = '{{ route("reception.orders") }}?saved=1';
    }
</script>
@endsection
