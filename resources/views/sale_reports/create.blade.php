@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.sale_report.create_title') }} {{ $supplier->name }}</h1>

    {{-- Résumé de l'étape 1 --}}
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-4">
                <strong>{{ __('messages.store.name') }}:</strong> {{ $store->name }}
            </div>
            <div class="col-md-4">
                <strong>{{ __('messages.sale_report.period_start') }}:</strong> {{ \Carbon\Carbon::parse($period_start)->format('d/m/Y') }}
            </div>
            <div class="col-md-4">
                <strong>{{ __('messages.sale_report.period_end') }}:</strong> {{ \Carbon\Carbon::parse($period_end)->format('d/m/Y') }}
            </div>
        </div>
        <div class="mt-2">
            <a href="{{ route('sale-reports.create', $supplier) }}?store_id={{ $store->id }}&period_start={{ $period_start }}&period_end={{ $period_end }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil"></i> {{ __('messages.sale_report.modify_parameters') }}
            </a>
        </div>
    </div>

    @if($hasPosSales)
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> {{ __('messages.sale_report.pos_sales_found') }}
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> {{ __('messages.sale_report.no_pos_sales_found') }}
        </div>
    @endif

    <form action="{{ route('sale-reports.store', $supplier) }}" method="POST">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}">
        <input type="hidden" name="period_start" value="{{ $period_start }}">
        <input type="hidden" name="report_date" value="{{ $period_end }}">

        {{-- Tableau des produits --}}
        <div class="mb-3">
            <h4>{{ __('messages.sale_report.supplier_products') }}</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.purchase_price') }}</th>
                        <th>{{ __('messages.sale_report.pos_quantity') }}</th>
                        <th>{{ __('messages.sale_report.quantity_sold') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        @php
                            $posQuantity = $posSalesQuantities[$product->id] ?? 0;
                        @endphp
                        <tr class="{{ $posQuantity > 0 ? 'table-success' : '' }}">
                            <td>{{ $product->ean }}</td>
                            <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                            <td>{{ number_format($product->pivot->purchase_price ?? 0, 2) }} &euro;</td>
                            <td>
                                @if($posQuantity > 0)
                                    <span class="badge bg-primary">{{ $posQuantity }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td>
                                <input type="number"
                                       name="products[{{ $product->id }}][quantity_sold]"
                                       min="0"
                                       value="{{ old('products.' . $product->id . '.quantity_sold', $posQuantity) }}"
                                       class="form-control form-control-sm quantity-input"
                                       data-pos-quantity="{{ $posQuantity }}"
                                       required>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <td colspan="3"><strong>{{ __('messages.sale_report.total_estimated') }}</strong></td>
                        <td><strong id="total-pos-quantity">{{ array_sum($posSalesQuantities) }}</strong></td>
                        <td><strong id="total-quantity">-</strong></td>
                    </tr>
                </tfoot>
            </table>
            @error('products')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        {{-- Boutons --}}
        <div class="mb-3">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}
            </button>
            <button type="button" class="btn btn-outline-primary" id="reset-to-pos">
                <i class="bi bi-arrow-counterclockwise"></i> {{ __('messages.sale_report.reset_to_pos') }}
            </button>
            <a href="{{ route('suppliers.edit', $supplier) }}#sales-reports" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const totalQuantityEl = document.getElementById('total-quantity');
    const resetBtn = document.getElementById('reset-to-pos');

    function updateTotal() {
        let total = 0;
        quantityInputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        totalQuantityEl.textContent = total;
    }

    quantityInputs.forEach(input => {
        input.addEventListener('input', updateTotal);
    });

    resetBtn.addEventListener('click', function() {
        quantityInputs.forEach(input => {
            input.value = input.dataset.posQuantity || 0;
        });
        updateTotal();
    });

    // Initial calculation
    updateTotal();
});
</script>
@endsection
