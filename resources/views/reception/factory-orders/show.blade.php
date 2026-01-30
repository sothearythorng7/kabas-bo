@extends('reception.layouts.app')

@section('title', __('messages.reception.receive_factory_order'))

@section('content')
<div class="header">
    <a href="{{ route('reception.factory-orders') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ __('messages.reception.back') }}
    </a>
    <div class="header-title">{{ $order->supplier->name }}</div>
    <div style="width: 60px;"></div>
</div>

<div class="container" style="padding-bottom: 160px;">
    <div class="card mb-4">
        <div class="flex justify-between">
            <div>
                <div class="text-sm text-muted">{{ __('messages.reception.order') }} #{{ $order->id }}</div>
                <div class="font-bold">{{ $order->created_at->format('d/m/Y') }}</div>
            </div>
            <div class="text-right">
                <div class="text-sm text-muted">{{ __('messages.reception.destination') }}</div>
                <div class="font-bold">{{ $order->destinationStore->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="text-sm text-muted mb-4">
        {{ __('messages.reception.enter_quantities_raw_materials') }}
    </div>

    @foreach($order->rawMaterials as $material)
        @php
            $orderedQty = $material->pivot->quantity_ordered;
            $receivedQty = $receivedQuantities[$material->id] ?? 0;
            $materialName = $material->name;
            $unit = $material->unit ?? '';
        @endphp
        <div class="product-item" data-material-id="{{ $material->id }}" style="flex-direction: column; align-items: stretch;">
            <div class="product-name" style="margin-bottom: 8px; white-space: normal; line-height: 1.3;">
                🏭 {{ $materialName }}
                @if($unit)
                    <span style="color: var(--text-light); font-size: 12px;">({{ $unit }})</span>
                @endif
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 60px; height: 60px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    📦
                </div>
                <div class="product-info" style="flex: 1;">
                    <div class="product-meta">
                        {{ __('messages.reception.ordered') }}: <strong>{{ $orderedQty }}</strong>
                        @if($receivedQty > 0)
                            &bull; <span style="color: var(--success);">{{ __('messages.reception.received') }}: {{ $receivedQty }}</span>
                        @endif
                    </div>
                </div>
                <div class="quantity-control">
                    <button type="button" class="quantity-btn" onclick="changeQty({{ $material->id }}, -1)">-</button>
                    <input type="number"
                           class="quantity-input"
                           id="qty-{{ $material->id }}"
                           value="{{ $receivedQty }}"
                           min="0"
                           max="99999"
                           step="0.01"
                           onchange="saveQty({{ $material->id }})"
                           inputmode="decimal">
                    <button type="button" class="quantity-btn" onclick="changeQty({{ $material->id }}, 1)">+</button>
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
        {{ __('messages.reception.save_continue_later') }}
    </button>
    <form action="{{ route('reception.factory-orders.finalize', $order) }}" method="POST" id="finalizeForm">
        @csrf
        <button type="submit" class="btn btn-success" id="finalizeBtn" style="width: 100%;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ __('messages.reception.finalize_reception') }}
        </button>
    </form>
</div>
@endsection

@section('scripts')
<script>
    const orderId = {{ $order->id }};
    let saveTimeout = {};
    let saving = {};

    function changeQty(materialId, delta) {
        const input = document.getElementById('qty-' + materialId);
        let value = parseFloat(input.value) || 0;
        value = Math.max(0, value + delta);
        input.value = value;
        saveQty(materialId);
    }

    function saveQty(materialId) {
        // Debounce saves
        if (saveTimeout[materialId]) {
            clearTimeout(saveTimeout[materialId]);
        }

        saveTimeout[materialId] = setTimeout(() => {
            doSave(materialId);
        }, 500);
    }

    async function doSave(materialId) {
        if (saving[materialId]) return;
        saving[materialId] = true;

        const input = document.getElementById('qty-' + materialId);
        const quantity = parseFloat(input.value) || 0;

        // Visual feedback
        input.style.background = '#fef3c7';

        try {
            const response = await fetch('{{ route("reception.factory-orders.receive-item", $order) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    raw_material_id: materialId,
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

        saving[materialId] = false;
    }

    // Confirm before finalizing
    document.getElementById('finalizeForm').addEventListener('submit', function(e) {
        if (!confirm('{{ __("messages.reception.confirm_finalize") }}')) {
            e.preventDefault();
        }
    });

    // Save partial and go back to list
    async function savePartial() {
        const btn = document.getElementById('savePartialBtn');
        btn.disabled = true;
        btn.innerHTML = '<span>{{ __("messages.reception.saving") }}...</span>';

        // Wait for any pending saves to complete
        await new Promise(resolve => setTimeout(resolve, 600));

        // Check if any save is still in progress
        const stillSaving = Object.values(saving).some(v => v);
        if (stillSaving) {
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        // Redirect back to factory orders list with success message
        window.location.href = '{{ route("reception.factory-orders") }}?saved=1';
    }
</script>
@endsection
