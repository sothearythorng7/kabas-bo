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

    <form action="{{ route('sale-reports.store', $supplier) }}" method="POST">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}">
        <input type="hidden" name="period_start" value="{{ $period_start }}">
        <input type="hidden" name="report_date" value="{{ $period_end }}">

        {{-- Tableau des produits --}}
        <div class="mb-3">
            <h4>{{ __('messages.sale_report.supplier_products') }}</h4>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('messages.product.barcode') }}</th>
                            <th>{{ __('messages.product.name') }}</th>
                            <th class="text-center">{{ __('messages.sale_report.old_stock') }}</th>
                            <th class="text-center">{{ __('messages.sale_report.refill') }}</th>
                            <th class="text-center">{{ __('messages.sale_report.stock_on_hand') }}</th>
                            <th class="text-center">{{ __('messages.sale_report.quantity_sold') }}</th>
                            <th class="text-end">{{ __('messages.sale_report.cost_price') }}</th>
                            <th class="text-end">{{ __('messages.sale_report.selling_price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @php
                                $posQuantity = $posSalesQuantities[$product->id] ?? 0;
                                $refillQty = $refillQuantities[$product->id] ?? 0;
                                $currentStock = $currentStockQuantities[$product->id] ?? 0;
                                $oldStock = $oldStockQuantities[$product->id] ?? 0;
                                $costPrice = $product->pivot->purchase_price ?? 0;
                                $sellingPriceUnit = $product->price ?? 0;
                            @endphp
                            <tr data-product-id="{{ $product->id }}"
                                data-cost-price="{{ $costPrice }}"
                                data-selling-price-unit="{{ $sellingPriceUnit }}"
                                data-pos-quantity="{{ $posQuantity }}">
                                <td>{{ $product->ean }}</td>
                                <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                <td class="text-center" style="width: 100px;">
                                    <input type="number"
                                           name="products[{{ $product->id }}][old_stock]"
                                           min="0"
                                           value="{{ old('products.' . $product->id . '.old_stock', $oldStock) }}"
                                           class="form-control form-control-sm text-center old-stock-input"
                                           required>
                                </td>
                                <td class="text-center" style="width: 100px;">
                                    <input type="number"
                                           name="products[{{ $product->id }}][refill]"
                                           min="0"
                                           value="{{ old('products.' . $product->id . '.refill', $refillQty) }}"
                                           class="form-control form-control-sm text-center refill-input"
                                           required>
                                </td>
                                <td class="text-center" style="width: 100px;">
                                    <input type="number"
                                           name="products[{{ $product->id }}][stock_on_hand]"
                                           min="0"
                                           value="{{ old('products.' . $product->id . '.stock_on_hand', $currentStock) }}"
                                           class="form-control form-control-sm text-center stock-on-hand-input"
                                           required>
                                </td>
                                <td class="text-center quantity-sold-cell">
                                    <input type="hidden" name="products[{{ $product->id }}][quantity_sold]" value="{{ $posQuantity }}" class="quantity-sold-input">
                                    <strong>{{ $posQuantity }}</strong>
                                </td>
                                <td class="text-end cost-price-cell">
                                    $ 0.00
                                </td>
                                <td class="text-end selling-price-cell">
                                    $ 0.00
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <td colspan="2"><strong>Total</strong></td>
                            <td class="text-center"><strong id="total-old-stock">0</strong></td>
                            <td class="text-center"><strong id="total-refill">0</strong></td>
                            <td class="text-center"><strong id="total-stock-on-hand">0</strong></td>
                            <td class="text-center"><strong id="total-quantity-sold">0</strong></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @error('products')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            {{-- Summary --}}
            <div class="card mt-3" style="max-width: 350px;">
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td><strong><em>{{ __('messages.sale_report.total_sale_amount') }}</em></strong></td>
                            <td class="text-end"><strong>$</strong></td>
                            <td class="text-end" style="width: 80px;"><strong id="summary-total-sale">0.00</strong></td>
                        </tr>
                        <tr>
                            <td><strong><em>{{ __('messages.sale_report.total_pay_amount') }}</em></strong></td>
                            <td class="text-end"><strong>$</strong></td>
                            <td class="text-end"><strong id="summary-total-pay">0.00</strong></td>
                        </tr>
                        <tr class="table-success">
                            <td><strong><em>{{ __('messages.sale_report.net_profit') }}</em></strong></td>
                            <td class="text-end"><strong>$</strong></td>
                            <td class="text-end"><strong id="summary-net-profit">0.00</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Boutons --}}
        <div class="mb-3">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}
            </button>
            <a href="{{ route('suppliers.edit', $supplier) }}#sales-reports" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr[data-product-id]');

    function formatMoney(value) {
        return value.toFixed(2);
    }

    function calculateRow(row) {
        const oldStock = parseInt(row.querySelector('.old-stock-input').value) || 0;
        const refill = parseInt(row.querySelector('.refill-input').value) || 0;
        const stockOnHand = parseInt(row.querySelector('.stock-on-hand-input').value) || 0;
        const costPrice = parseFloat(row.dataset.costPrice) || 0;
        const sellingPriceUnit = parseFloat(row.dataset.sellingPriceUnit) || 0;
        const posQuantity = parseInt(row.dataset.posQuantity) || 0;

        // Utiliser les ventes POS comme quantité vendue
        const quantitySold = posQuantity;
        const totalCost = costPrice * quantitySold;
        const totalSelling = sellingPriceUnit * quantitySold;

        row.querySelector('.cost-price-cell').textContent = '$ ' + formatMoney(totalCost);
        row.querySelector('.selling-price-cell').textContent = '$ ' + formatMoney(totalSelling);

        return { oldStock, refill, stockOnHand, quantitySold, totalCost, totalSelling };
    }

    function updateTotals() {
        let totalOldStock = 0;
        let totalRefill = 0;
        let totalStockOnHand = 0;
        let totalQuantitySold = 0;
        let totalPay = 0;
        let totalSale = 0;

        rows.forEach(row => {
            const result = calculateRow(row);
            totalOldStock += result.oldStock;
            totalRefill += result.refill;
            totalStockOnHand += result.stockOnHand;
            totalQuantitySold += result.quantitySold;
            totalPay += result.totalCost;
            totalSale += result.totalSelling;
        });

        document.getElementById('total-old-stock').textContent = totalOldStock;
        document.getElementById('total-refill').textContent = totalRefill;
        document.getElementById('total-stock-on-hand').textContent = totalStockOnHand;
        document.getElementById('total-quantity-sold').textContent = totalQuantitySold;

        document.getElementById('summary-total-sale').textContent = formatMoney(totalSale);
        document.getElementById('summary-total-pay').textContent = formatMoney(totalPay);
        document.getElementById('summary-net-profit').textContent = formatMoney(totalSale - totalPay);
    }

    // Add event listeners to all inputs
    rows.forEach(row => {
        row.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', updateTotals);
        });
    });

    // Initial calculation
    updateTotals();
});
</script>
@endsection
