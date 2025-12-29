@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.refill.title') }} #{{ $refill->id }} - {{ $supplier->name }}</h1>

    <div class="mb-3">
        <strong>{{ __('messages.refill.destination_store') }} :</strong>
        {{ $refill->destinationStore?->name ?? '-' }}
    </div>

    <form id="refill-update-form" action="{{ route('refills.updateQuantities', [$supplier, $refill]) }}" method="POST">
        @csrf
        @method('PUT')

        <h3>{{ __('messages.refill.products_received') }}</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ __('messages.stock_value.ean') }}</th>
                    <th>{{ __('messages.product.name') }}</th>
                    <th>{{ __('messages.product.brand_label') }}</th>
                    <th>{{ __('messages.refill.quantity_received') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($refill->products as $product)
                    <tr>
                        <td>{{ $product->ean }}</td>
                        <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                        <td>{{ $product->brand?->name ?? '-' }}</td>
                        <td>
                            <input type="number"
                                   name="products[{{ $product->id }}][quantity_received]"
                                   value="{{ $product->pivot->quantity_received }}"
                                   data-original="{{ $product->pivot->quantity_received }}"
                                   min="0"
                                   class="form-control form-control-sm quantity-input"
                                   style="width: 100px;">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="d-flex gap-2">
            <a href="{{ route('suppliers.edit', $supplier) }}#refills" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
            </a>
            <button type="submit" id="save-btn" class="btn btn-success" disabled>
                <i class="bi bi-floppy-fill"></i> {{ __('messages.refill.save_changes') }}
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('refill-update-form');
    const saveBtn = document.getElementById('save-btn');
    const quantityInputs = document.querySelectorAll('.quantity-input');

    function checkForChanges() {
        let hasChanges = false;
        quantityInputs.forEach(input => {
            const original = parseInt(input.dataset.original);
            const current = parseInt(input.value) || 0;
            if (original !== current) {
                hasChanges = true;
                input.classList.add('is-changed');
            } else {
                input.classList.remove('is-changed');
            }
        });
        saveBtn.disabled = !hasChanges;
    }

    quantityInputs.forEach(input => {
        input.addEventListener('input', checkForChanges);
    });

    // Initial check
    checkForChanges();
});
</script>

<style>
.quantity-input.is-changed {
    border-color: #ffc107;
    background-color: #fffbe6;
}
</style>
@endsection
