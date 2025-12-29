@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.resellers.returns') }} - {{ __('messages.btn.create') }} : {{ $reseller->name }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('resellers.returns.store', $reseller->id) }}" id="returnForm">
        @csrf

        <!-- Destination du retour -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.resellers.return_destination') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.resellers.destination_store') }} <span class="text-danger">*</span></label>
                        <select name="destination_store_id" class="form-select" required>
                            <option value="">-- {{ __('messages.btn.select') }} --</option>
                            @foreach($destinations as $dest)
                                <option value="{{ $dest->id }}" {{ old('destination_store_id') == $dest->id ? 'selected' : '' }}>
                                    {{ $dest->name }} ({{ ucfirst($dest->type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.resellers.note') }}</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="{{ __('messages.resellers.return_note_placeholder') }}">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recherche produit -->
        <div class="mb-3">
            <input type="text" id="productFilter" class="form-control" placeholder="{{ __('messages.stock_value.search_placeholder') }}">
        </div>

        @if($products->isEmpty())
            <div class="alert alert-warning">
                {{ __('messages.resellers.no_stock_to_return') }}
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped" id="productTable">
                    <thead>
                        <tr>
                            <th>{{ __('messages.stock_value.ean') }}</th>
                            <th>{{ __('messages.product.name') }}</th>
                            <th class="d-none d-md-table-cell">{{ __('messages.product.brand') }}</th>
                            <th class="text-center">{{ __('messages.resellers.current_stock') }}</th>
                            <th class="text-center" style="width: 100px;">{{ __('messages.resellers.return_quantity') }}</th>
                            <th class="d-none d-md-table-cell">{{ __('messages.resellers.return_reason') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @php $currentStock = $stock[$product->id] ?? 0; @endphp
                            <tr data-product-id="{{ $product->id }}" class="product-row">
                                <td class="small">{{ $product->ean }}</td>
                                <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                <td class="d-none d-md-table-cell">{{ $product->brand->name ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $currentStock }}</span>
                                </td>
                                <td>
                                    <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                                    <input type="number"
                                        name="items[{{ $product->id }}][quantity]"
                                        min="0"
                                        max="{{ $currentStock }}"
                                        value="0"
                                        class="form-control form-control-sm text-center quantity-input"
                                        data-max="{{ $currentStock }}">
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <input type="text"
                                        name="items[{{ $product->id }}][reason]"
                                        class="form-control form-control-sm"
                                        placeholder="{{ __('messages.resellers.reason_placeholder') }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <div>
                    <strong>{{ __('messages.resellers.total_items_to_return') }}:</strong>
                    <span id="totalItems" class="badge bg-primary fs-5">0</span>
                </div>
                <div>
                    <a href="{{ route('resellers.show', $reseller->id) }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
                    <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                        <i class="bi bi-box-arrow-left"></i> {{ __('messages.resellers.create_return') }}
                    </button>
                </div>
            </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('returnForm');
    const totalSpan = document.getElementById('totalItems');
    const submitBtn = document.getElementById('submitBtn');

    function updateTotal() {
        let total = 0;
        form.querySelectorAll('.quantity-input').forEach(input => {
            total += parseInt(input.value) || 0;
        });
        totalSpan.textContent = total;
        submitBtn.disabled = total === 0;
    }

    // Filtre de recherche
    document.getElementById('productFilter').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('.product-row').forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    });

    // Event sur les inputs quantité
    form.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const max = parseInt(this.dataset.max) || 0;
            let val = parseInt(this.value) || 0;
            if (val > max) val = max;
            if (val < 0) val = 0;
            this.value = val;
            updateTotal();
        });
    });

    updateTotal();
});
</script>
@endpush
