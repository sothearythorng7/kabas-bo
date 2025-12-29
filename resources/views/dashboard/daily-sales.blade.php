@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">
            <i class="bi bi-receipt"></i> {{ __('messages.daily_sales.title') }} - {{ $store->name }}
        </h1>
        <a href="{{ route('dashboard') }}?date={{ $date->format('Y-m-d') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>

    <!-- Sélecteur de date -->
    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <div class="d-flex justify-content-center align-items-center gap-3">
                <a href="{{ route('dashboard.daily-sales', ['store' => $store->id, 'date' => $date->copy()->subDay()->format('Y-m-d')]) }}"
                   class="btn btn-outline-primary">
                    <i class="bi bi-chevron-left"></i>
                </a>

                <form id="dateForm" class="d-flex align-items-center gap-2">
                    <input type="date"
                           id="dateSelector"
                           name="date"
                           value="{{ $date->format('Y-m-d') }}"
                           max="{{ now()->format('Y-m-d') }}"
                           class="form-control"
                           style="width: auto;">
                    <span class="fw-bold text-primary">
                        {{ $date->translatedFormat('l d F Y') }}
                    </span>
                </form>

                @if($date->format('Y-m-d') < now()->format('Y-m-d'))
                    <a href="{{ route('dashboard.daily-sales', ['store' => $store->id, 'date' => $date->copy()->addDay()->format('Y-m-d')]) }}"
                       class="btn btn-outline-primary">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                @endif

                @if($date->format('Y-m-d') != now()->format('Y-m-d'))
                    <a href="{{ route('dashboard.daily-sales', ['store' => $store->id]) }}"
                       class="btn btn-primary ms-2">
                        {{ __('messages.daily_sales.today') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Résumé du jour -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                        {{ __('messages.daily_sales.total_sales') }}
                    </div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $sales->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        {{ __('messages.daily_sales.total_revenue') }}
                    </div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">${{ number_format($totalRevenue, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-danger shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                        {{ __('messages.daily_sales.total_discounts') }}
                    </div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">${{ number_format($totalDiscounts, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($sales->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> {{ __('messages.daily_sales.no_sales') }}
        </div>
    @else
        <!-- Tableau des ventes -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;"></th>
                                <th>{{ __('messages.daily_sales.time') }}</th>
                                <th>#</th>
                                <th>{{ __('messages.daily_sales.seller') }}</th>
                                <th class="text-center">{{ __('messages.daily_sales.items_count') }}</th>
                                <th>{{ __('messages.daily_sales.payment_method') }}</th>
                                <th class="text-end">{{ __('messages.daily_sales.before_discount') }}</th>
                                <th class="text-end">{{ __('messages.daily_sales.item_discounts') }}</th>
                                <th class="text-end">{{ __('messages.daily_sales.total_paid') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sales as $index => $sale)
                                @php
                                    // Calcul des montants pour cette vente
                                    $saleBeforeDiscount = 0;
                                    $saleItemDiscounts = 0;
                                    $allDiscounts = [];
                                    $totalItemsCount = $sale->items->sum('quantity');

                                    foreach ($sale->items as $item) {
                                        $itemTotal = $item->price * $item->quantity;
                                        $saleBeforeDiscount += $itemTotal;

                                        if (!empty($item->discounts)) {
                                            foreach ($item->discounts as $discount) {
                                                $discountAmount = 0;
                                                if (($discount['type'] ?? '') === 'percent') {
                                                    $discountAmount = $itemTotal * (($discount['value'] ?? 0) / 100);
                                                } else {
                                                    $discountAmount = ($discount['value'] ?? 0);
                                                }
                                                $saleItemDiscounts += $discountAmount;

                                                $allDiscounts[] = [
                                                    'label' => $discount['label'] ?? __('messages.daily_sales.item_discount'),
                                                    'type' => $discount['type'] ?? 'fixed',
                                                    'value' => $discount['value'] ?? 0,
                                                    'amount' => $discountAmount,
                                                    'level' => 'item',
                                                    'product' => $item->product->name['fr'] ?? $item->product->name['en'] ?? 'N/A'
                                                ];
                                            }
                                        }
                                    }

                                    // Réductions au niveau vente
                                    $saleDiscounts = 0;
                                    if (!empty($sale->discounts)) {
                                        $subtotal = $saleBeforeDiscount - $saleItemDiscounts;
                                        foreach ($sale->discounts as $discount) {
                                            $discountAmount = 0;
                                            if (($discount['type'] ?? '') === 'percent') {
                                                $discountAmount = $subtotal * (($discount['value'] ?? 0) / 100);
                                            } else {
                                                $discountAmount = ($discount['value'] ?? 0);
                                            }
                                            $saleDiscounts += $discountAmount;

                                            $allDiscounts[] = [
                                                'label' => $discount['label'] ?? __('messages.daily_sales.sale_discount'),
                                                'type' => $discount['type'] ?? 'fixed',
                                                'value' => $discount['value'] ?? 0,
                                                'amount' => $discountAmount,
                                                'level' => 'sale'
                                            ];
                                        }
                                    }

                                    $totalSaleDiscounts = $saleItemDiscounts + $saleDiscounts;
                                    $hasExchanges = $sale->exchanges->count() > 0;
                                @endphp

                                <!-- Ligne du tableau -->
                                <tr class="sale-row {{ $hasExchanges ? 'table-warning' : '' }}" data-sale-id="{{ $sale->id }}" style="cursor: pointer;">
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary toggle-detail" data-sale-id="{{ $sale->id }}" title="{{ __('messages.daily_sales.show_detail') }}">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <i class="bi bi-clock text-muted"></i> {{ $sale->created_at->format('H:i') }}
                                    </td>
                                    <td>
                                        <span class="text-muted">#{{ $sale->id }}</span>
                                    </td>
                                    <td>
                                        <i class="bi bi-person text-muted"></i> {{ $sale->shift->user->name ?? 'N/A' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $totalItemsCount }}</span>
                                    </td>
                                    <td>
                                        @if(!empty($sale->split_payments))
                                            @foreach($sale->split_payments as $payment)
                                                <span class="badge bg-info">{{ $payment['payment_type'] ?? 'N/A' }}</span>
                                            @endforeach
                                        @elseif($sale->payment_type)
                                            <span class="badge bg-info">{{ $sale->payment_type }}</span>
                                        @endif
                                        @if($hasExchanges)
                                            <span class="badge bg-warning text-dark" title="{{ __('messages.daily_sales.has_exchange') }}">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">${{ number_format($saleBeforeDiscount, 2) }}</td>
                                    <td class="text-end">
                                        @if($totalSaleDiscounts > 0)
                                            <span class="text-danger">-${{ number_format($totalSaleDiscounts, 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success">${{ number_format($sale->total, 2) }}</strong>
                                    </td>
                                </tr>

                                <!-- Détail de la vente (caché par défaut) -->
                                <tr class="sale-detail" id="detail-{{ $sale->id }}" style="display: none;">
                                    <td colspan="9" class="p-0">
                                        <div class="card border-0 border-top m-0">
                                            <div class="card-body bg-light">
                                                <div class="row mb-3">
                                                    <div class="col-md-3">
                                                        <strong>{{ __('messages.daily_sales.seller') }}:</strong><br>
                                                        <span class="text-muted">
                                                            <i class="bi bi-person"></i> {{ $sale->shift->user->name ?? 'N/A' }}
                                                        </span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>{{ __('messages.daily_sales.amount_before_discount') }}:</strong><br>
                                                        <span class="text-muted">${{ number_format($saleBeforeDiscount, 2) }}</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>{{ __('messages.daily_sales.total_discount') }}:</strong><br>
                                                        <span class="text-danger">-${{ number_format($totalSaleDiscounts, 2) }}</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>{{ __('messages.daily_sales.total_paid') }}:</strong><br>
                                                        <span class="text-success fw-bold" style="font-size: 1.2em;">${{ number_format($sale->total, 2) }}</span>
                                                    </div>
                                                </div>

                                                <!-- Tableau des produits -->
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-striped mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>{{ __('messages.daily_sales.product') }}</th>
                                                                <th class="text-center">{{ __('messages.daily_sales.quantity') }}</th>
                                                                <th class="text-end">{{ __('messages.daily_sales.unit_price') }}</th>
                                                                <th class="text-end">{{ __('messages.daily_sales.before_discount') }}</th>
                                                                <th class="text-end">{{ __('messages.daily_sales.item_discounts') }}</th>
                                                                <th class="text-end">{{ __('messages.daily_sales.after_discount') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($sale->items as $item)
                                                                @php
                                                                    $itemTotal = $item->price * $item->quantity;
                                                                    $itemDiscountTotal = 0;

                                                                    if (!empty($item->discounts)) {
                                                                        foreach ($item->discounts as $discount) {
                                                                            if (($discount['type'] ?? '') === 'percent') {
                                                                                $itemDiscountTotal += $itemTotal * (($discount['value'] ?? 0) / 100);
                                                                            } else {
                                                                                $itemDiscountTotal += ($discount['value'] ?? 0);
                                                                            }
                                                                        }
                                                                    }

                                                                    $itemAfterDiscount = $itemTotal - $itemDiscountTotal;
                                                                    $isExchanged = !is_null($item->exchanged_at);
                                                                    $isFromExchange = !is_null($item->added_via_exchange_id);
                                                                @endphp
                                                                <tr class="{{ $isExchanged ? 'table-secondary text-decoration-line-through' : '' }}">
                                                                    <td>
                                                                        @if($item->is_custom_service)
                                                                            <i class="bi bi-gear text-primary"></i> {{ $item->custom_service_description ?? __('messages.daily_sales.custom_service') }}
                                                                        @else
                                                                            {{ $item->product->name['fr'] ?? $item->product->name['en'] ?? 'Produit supprimé' }}
                                                                        @endif
                                                                        @if($item->is_delivery)
                                                                            <span class="badge bg-warning text-dark ms-1">
                                                                                <i class="bi bi-truck"></i> {{ __('messages.daily_sales.delivery') }}
                                                                            </span>
                                                                        @elseif($item->is_custom_service)
                                                                            <span class="badge bg-primary text-white ms-1">
                                                                                <i class="bi bi-gear"></i> {{ __('messages.daily_sales.custom_service') }}
                                                                            </span>
                                                                        @endif
                                                                        @if($isExchanged)
                                                                            <span class="badge bg-danger ms-1">
                                                                                <i class="bi bi-arrow-left-right"></i> {{ __('messages.daily_sales.exchanged') }}
                                                                            </span>
                                                                        @elseif($isFromExchange)
                                                                            <span class="badge bg-info ms-1">
                                                                                <i class="bi bi-plus-circle"></i> {{ __('messages.daily_sales.via_exchange') }}
                                                                            </span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">{{ $item->quantity }}</td>
                                                                    <td class="text-end">${{ number_format($item->price, 2) }}</td>
                                                                    <td class="text-end">${{ number_format($itemTotal, 2) }}</td>
                                                                    <td class="text-end text-danger">
                                                                        @if($itemDiscountTotal > 0)
                                                                            -${{ number_format($itemDiscountTotal, 2) }}
                                                                        @else
                                                                            -
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-end fw-bold">${{ number_format($itemAfterDiscount, 2) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <!-- Résumé des réductions -->
                                                @if(count($allDiscounts) > 0)
                                                    <div class="mt-3">
                                                        <h6 class="text-danger">
                                                            <i class="bi bi-percent"></i> {{ __('messages.daily_sales.discounts_summary') }}
                                                        </h6>
                                                        <ul class="list-group list-group-flush">
                                                            @foreach($allDiscounts as $discount)
                                                                <li class="list-group-item d-flex justify-content-between align-items-center py-1 bg-transparent">
                                                                    <span>
                                                                        @if($discount['level'] === 'item')
                                                                            <span class="badge bg-secondary me-1">{{ __('messages.daily_sales.item') }}</span>
                                                                            <small class="text-muted">{{ $discount['product'] }}</small> -
                                                                        @else
                                                                            <span class="badge bg-primary me-1">{{ __('messages.daily_sales.sale') }}</span>
                                                                        @endif
                                                                        {{ $discount['label'] }}
                                                                        @if($discount['type'] === 'percent')
                                                                            ({{ $discount['value'] }}%)
                                                                        @else
                                                                            (${{ number_format($discount['value'], 2) }})
                                                                        @endif
                                                                    </span>
                                                                    <span class="text-danger">-${{ number_format($discount['amount'], 2) }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                <!-- Historique des échanges -->
                                                @if($sale->exchanges->count() > 0)
                                                    <div class="mt-4 border-top pt-3">
                                                        <h6 class="text-warning">
                                                            <i class="bi bi-arrow-left-right"></i> {{ __('messages.daily_sales.exchange_history') }}
                                                        </h6>
                                                        @foreach($sale->exchanges as $exchange)
                                                            <div class="card border-warning mb-2">
                                                                <div class="card-header bg-warning bg-opacity-25 py-2">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <span>
                                                                            <strong>{{ __('messages.daily_sales.exchange') }} #{{ $exchange->id }}</strong>
                                                                            <small class="text-muted ms-2">{{ $exchange->created_at->format('d/m/Y H:i') }}</small>
                                                                        </span>
                                                                        @if($exchange->generatedVoucher)
                                                                            <span class="badge bg-success">
                                                                                <i class="bi bi-ticket-perforated"></i>
                                                                                Voucher: {{ $exchange->generatedVoucher->code }} (${{ number_format($exchange->generatedVoucher->amount, 2) }})
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="card-body py-2">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <small class="text-danger fw-bold"><i class="bi bi-dash-circle"></i> {{ __('messages.daily_sales.returned_products') }}:</small>
                                                                            <ul class="list-unstyled mb-0 ms-2">
                                                                                @foreach($exchange->items->where('type', 'returned') as $exchangeItem)
                                                                                    <li>
                                                                                        <small>
                                                                                            - {{ $exchangeItem->product->name['fr'] ?? $exchangeItem->product->name['en'] ?? 'Produit' }}
                                                                                            x{{ $exchangeItem->quantity }}
                                                                                            (${{ number_format($exchangeItem->unit_price, 2) }})
                                                                                        </small>
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>
                                                                            <small class="text-danger">{{ __('messages.daily_sales.credit') }}: ${{ number_format($exchange->return_total, 2) }}</small>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <small class="text-success fw-bold"><i class="bi bi-plus-circle"></i> {{ __('messages.daily_sales.new_products') }}:</small>
                                                                            @if($exchange->items->where('type', 'new')->count() > 0)
                                                                                <ul class="list-unstyled mb-0 ms-2">
                                                                                    @foreach($exchange->items->where('type', 'new') as $exchangeItem)
                                                                                        <li>
                                                                                            <small>
                                                                                                + {{ $exchangeItem->product->name['fr'] ?? $exchangeItem->product->name['en'] ?? 'Produit' }}
                                                                                                x{{ $exchangeItem->quantity }}
                                                                                                (${{ number_format($exchangeItem->unit_price, 2) }})
                                                                                            </small>
                                                                                        </li>
                                                                                    @endforeach
                                                                                </ul>
                                                                                <small class="text-success">Total: ${{ number_format($exchange->new_items_total, 2) }}</small>
                                                                            @else
                                                                                <small class="text-muted ms-2">{{ __('messages.daily_sales.none') }}</small>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    <div class="mt-2 pt-2 border-top">
                                                                        <small>
                                                                            <strong>Balance:</strong>
                                                                            <span class="{{ $exchange->balance > 0 ? 'text-success' : ($exchange->balance < 0 ? 'text-danger' : '') }}">
                                                                                ${{ number_format($exchange->balance, 2) }}
                                                                            </span>
                                                                            @if($exchange->balance > 0)
                                                                                ({{ __('messages.daily_sales.customer_credit') }})
                                                                            @elseif($exchange->balance < 0)
                                                                                ({{ __('messages.daily_sales.payment_received') }})
                                                                            @endif
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('dateSelector').addEventListener('change', function() {
        const date = this.value;
        const url = new URL(window.location.href);
        url.searchParams.set('date', date);
        window.location.href = url.toString();
    });

    // Toggle sale detail
    document.querySelectorAll('.toggle-detail').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const saleId = this.dataset.saleId;
            const detailRow = document.getElementById('detail-' + saleId);
            const icon = this.querySelector('i');

            if (detailRow.style.display === 'none') {
                detailRow.style.display = 'table-row';
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-up');
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary');
                this.title = '{{ __('messages.daily_sales.hide_detail') }}';
            } else {
                detailRow.style.display = 'none';
                icon.classList.remove('bi-chevron-up');
                icon.classList.add('bi-chevron-down');
                this.classList.remove('btn-primary');
                this.classList.add('btn-outline-primary');
                this.title = '{{ __('messages.daily_sales.show_detail') }}';
            }
        });
    });

    // Click on row to toggle detail
    document.querySelectorAll('.sale-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.toggle-detail')) return;
            const saleId = this.dataset.saleId;
            document.querySelector('.toggle-detail[data-sale-id="' + saleId + '"]').click();
        });
    });
</script>
@endpush
