@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.stock_loss.edit_title') }} - {{ $stockLoss->reference }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stock-losses.update', $stockLoss) }}" method="POST" id="loss-form">
        @csrf
        @method('PUT')

        <div class="row mb-4">
            <div class="col-md-3">
                <label class="form-label fw-bold">{{ __('messages.stock_loss.store') }}</label>
                <input type="text" class="form-control" value="{{ $stockLoss->store->name }}" disabled>
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label fw-bold">{{ __('messages.stock_loss.type') }}</label>
                <select name="type" id="type" class="form-select" required>
                    <option value="pure_loss" {{ $stockLoss->type === 'pure_loss' ? 'selected' : '' }}>{{ __('messages.stock_loss.type_pure_loss') }}</option>
                    <option value="supplier_refund" {{ $stockLoss->type === 'supplier_refund' ? 'selected' : '' }}>{{ __('messages.stock_loss.type_supplier_refund') }}</option>
                </select>
            </div>
            <div class="col-md-3" id="supplier-field" style="{{ $stockLoss->type === 'supplier_refund' ? '' : 'display:none;' }}">
                <label for="supplier_id" class="form-label fw-bold">{{ __('messages.stock_loss.supplier') }}</label>
                <select name="supplier_id" id="supplier_id" class="form-select" {{ $stockLoss->type === 'supplier_refund' ? 'required' : '' }}>
                    <option value="">{{ __('messages.stock_loss.select_supplier') }}</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ $stockLoss->supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="reason" class="form-label fw-bold">{{ __('messages.stock_loss.reason') }}</label>
                <input type="text" name="reason" id="reason" class="form-control" value="{{ $stockLoss->reason }}">
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <label for="notes" class="form-label">{{ __('messages.stock_loss.notes') }}</label>
                <input type="text" name="notes" id="notes" class="form-control" value="{{ $stockLoss->notes }}" placeholder="{{ __('messages.stock_loss.notes_placeholder') }}">
            </div>
        </div>

        {{-- Current items --}}
        <h5>{{ __('messages.stock_loss.products') }}</h5>
        <table class="table table-striped" id="products-table">
            <thead>
                <tr>
                    <th>EAN</th>
                    <th>{{ __('messages.product.name') }}</th>
                    <th>{{ __('messages.product.brand_label') }}</th>
                    <th>{{ __('messages.stock_loss.quantity') }}</th>
                    <th>{{ __('messages.stock_loss.unit_cost') }}</th>
                    <th>{{ __('messages.stock_loss.loss_reason') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="products-tbody">
                @foreach($stockLoss->items as $index => $item)
                    <tr>
                        <td>{{ $item->product->ean ?? '-' }}</td>
                        <td>{{ is_array($item->product->name) ? ($item->product->name[app()->getLocale()] ?? reset($item->product->name)) : $item->product->name }}</td>
                        <td>{{ $item->product->brand?->name ?? '-' }}</td>
                        <td>
                            <input type="number" name="products[{{ $index }}][quantity]" class="form-control form-control-sm quantity-input"
                                   value="{{ $item->quantity }}" min="1" style="max-width: 80px;" required>
                            <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                        </td>
                        <td>
                            <input type="number" name="products[{{ $index }}][unit_cost]" class="form-control form-control-sm cost-input"
                                   value="{{ $item->unit_cost }}" min="0" step="0.00001" style="max-width: 100px;" required>
                        </td>
                        <td>
                            <input type="text" name="products[{{ $index }}][loss_reason]" class="form-control form-control-sm"
                                   value="{{ $item->loss_reason }}" style="max-width: 150px;">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-item" onclick="this.closest('tr').remove(); updateEditSummary();">
                                <i class="bi bi-x"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="alert alert-secondary" id="summary-section">
            <strong>{{ __('messages.stock_loss.summary') }}:</strong>
            <span id="total-items">{{ $stockLoss->items->count() }}</span> {{ __('messages.stock_loss.products_selected') }},
            <span id="total-quantity">{{ $stockLoss->total_quantity }}</span> {{ __('messages.stock_loss.units_total') }},
            {{ __('messages.stock_loss.total_value') }}: $<span id="total-value">{{ number_format($stockLoss->total_value, 2) }}</span>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> {{ __('messages.btn.save') }}
            </button>
            <a href="{{ route('stock-losses.show', $stockLoss) }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const supplierField = document.getElementById('supplier-field');
    const supplierSelect = document.getElementById('supplier_id');

    typeSelect.addEventListener('change', function() {
        if (this.value === 'supplier_refund') {
            supplierField.style.display = '';
            supplierSelect.required = true;
        } else {
            supplierField.style.display = 'none';
            supplierSelect.required = false;
            supplierSelect.value = '';
        }
    });

    // Update summary on input changes
    document.getElementById('products-table').addEventListener('input', updateEditSummary);
});

function updateEditSummary() {
    const rows = document.querySelectorAll('#products-tbody tr');
    let totalItems = rows.length;
    let totalQty = 0;
    let totalVal = 0;

    rows.forEach(row => {
        const qty = parseInt(row.querySelector('.quantity-input')?.value) || 0;
        const cost = parseFloat(row.querySelector('.cost-input')?.value) || 0;
        totalQty += qty;
        totalVal += qty * cost;
    });

    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('total-quantity').textContent = totalQty;
    document.getElementById('total-value').textContent = totalVal.toFixed(2);
}
</script>
@endpush
