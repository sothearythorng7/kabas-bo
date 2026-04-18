@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mt-4 mb-4">
    <h1 class="mb-0">{{ __('messages.main_dashboard.title') }}</h1>
    <div class="d-flex align-items-center gap-2">
        <label for="dashboard-date" class="form-label mb-0 text-muted">{{ __('messages.date') }}:</label>
        <input type="date" id="dashboard-date" class="form-control" style="width: auto;"
               value="{{ $selectedDate->format('Y-m-d') }}"
               max="{{ now()->format('Y-m-d') }}"
               onchange="changeDashboardDate(this.value)">
        @if(!$selectedDate->isToday())
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm" title="{{ __('messages.main_dashboard.back_to_today') }}">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        @endif
    </div>
</div>

<!-- Tableau des alertes produits -->
@if(($productsWithoutImages ?? 0) > 0 || ($productsWithoutDescriptionFr ?? 0) > 0 || ($productsWithoutDescriptionEn ?? 0) > 0 || ($productsOutOfStock ?? 0) > 0 || ($productsWithFakeOrEmptyEan ?? 0) > 0 || ($productsWithoutCategories ?? 0) > 0 || ($inactiveProducts ?? 0) > 0 || ($productsWithoutWeight ?? 0) > 0)
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-danger">
            <i class="bi bi-exclamation-triangle"></i> {{ __('messages.main_dashboard.products_with_issues') }}
        </h6>
        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#productAlertsContent" aria-expanded="false" aria-controls="productAlertsContent">
            <i class="bi bi-chevron-down" id="productAlertsIcon"></i> {{ __('messages.main_dashboard.show') }}
        </button>
    </div>
    <div class="card-body collapse" id="productAlertsContent">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.main_dashboard.problem_type') }}</th>
                        <th class="text-center" style="width: 150px;">{{ __('messages.main_dashboard.product_count') }}</th>
                        <th class="text-center" style="width: 150px;">{{ __('messages.main_dashboard.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if(($productsWithoutImages ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-image text-warning"></i> {{ __('messages.main_dashboard.products_without_images') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark fs-5">{{ $productsWithoutImages }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-warning" href="{{ route('dashboard.products-issues', ['type' => 'no_image']) }}">
                                <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_products') }}
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsWithoutDescriptionFr ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-file-text text-danger"></i> {{ __('messages.main_dashboard.products_without_desc_fr') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger fs-5">{{ $productsWithoutDescriptionFr }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-danger" href="{{ route('dashboard.products-issues', ['type' => 'no_description_fr']) }}">
                                <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_products') }}
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsWithoutDescriptionEn ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-file-text text-info"></i> {{ __('messages.main_dashboard.products_without_desc_en') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info fs-5">{{ $productsWithoutDescriptionEn }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-info" href="{{ route('dashboard.products-issues', ['type' => 'no_description_en']) }}">
                                <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_products') }}
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsOutOfStock ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-box-seam text-danger"></i> {{ __('messages.main_dashboard.products_out_of_stock') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger fs-5">{{ $productsOutOfStock }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-danger" href="{{ route('stocks.index') }}">
                                <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_stock') }}
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsWithFakeOrEmptyEan ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-upc-scan text-warning"></i> {{ __('messages.main_dashboard.products_fake_ean') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark fs-5">{{ $productsWithFakeOrEmptyEan }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-warning" href="{{ route('dashboard.products-issues', ['type' => 'fake_or_empty_ean']) }}">
                                <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_products') }}
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsWithoutCategories ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-bookmarks text-primary"></i> {{ __('messages.main_dashboard.products_no_category') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-5">{{ $productsWithoutCategories }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-primary" href="{{ route('dashboard.products-issues', ['type' => 'no_category']) }}">
                                <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_products') }}
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($inactiveProducts ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-toggle-off text-secondary"></i> {{ __('messages.main_dashboard.products_inactive') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary fs-5">{{ $inactiveProducts }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-secondary" href="{{ route('dashboard.products-issues', ['type' => 'inactive']) }}">
                                <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_products') }}
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsWithoutWeight ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-speedometer text-warning"></i> {{ __('messages.main_dashboard.products_no_weight') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark fs-5">{{ $productsWithoutWeight }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-warning" href="{{ route('dashboard.products-issues', ['type' => 'no_weight']) }}">
                                <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_products') }}
                            </a>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Alertes opérationnelles -->
@if(($resellerInvoicesUnpaid ?? 0) > 0 || ($consignmentInvoicesUnpaid ?? 0) > 0 || ($unreadContactMessages ?? 0) > 0)
<div class="row mb-4">
    @if(($resellerInvoicesUnpaid ?? 0) > 0)
    <div class="col-md-4">
        <div class="alert alert-warning d-flex align-items-center mb-0 h-100" role="alert">
            <i class="bi bi-receipt-cutoff fs-3 me-3"></i>
            <div class="flex-grow-1">
                <strong>{{ __('messages.main_dashboard.reseller_invoices_unpaid') }}</strong><br>
                <span class="fs-5 fw-bold">{{ $resellerInvoicesUnpaid }}</span>
                <small class="text-muted">— ${{ number_format($resellerInvoicesUnpaidTotal, 2) }}</small>
            </div>
            <a href="{{ route('reseller-invoices.index') }}" class="btn btn-warning btn-sm ms-2">
                <i class="bi bi-eye"></i>
            </a>
        </div>
    </div>
    @endif

    @if(($consignmentInvoicesUnpaid ?? 0) > 0)
    <div class="col-md-4">
        <div class="alert alert-danger d-flex align-items-center mb-0 h-100" role="alert">
            <i class="bi bi-file-earmark-text fs-3 me-3"></i>
            <div class="flex-grow-1">
                <strong>{{ __('messages.main_dashboard.consignment_invoices_unpaid') }}</strong><br>
                <span class="fs-5 fw-bold">{{ $consignmentInvoicesUnpaid }}</span>
                <small class="text-muted">— ${{ number_format($consignmentInvoicesUnpaidTotal, 2) }}</small>
            </div>
            <a href="{{ route('supplier-orders.overview') }}" class="btn btn-danger btn-sm ms-2">
                <i class="bi bi-eye"></i>
            </a>
        </div>
    </div>
    @endif

    @if(($unreadContactMessages ?? 0) > 0)
    <div class="col-md-4">
        <div class="alert alert-info d-flex align-items-center mb-0 h-100" role="alert">
            <i class="bi bi-envelope-exclamation fs-3 me-3"></i>
            <div class="flex-grow-1">
                <strong>{{ __('messages.main_dashboard.unread_messages') }}</strong><br>
                <span class="fs-5 fw-bold">{{ $unreadContactMessages }}</span>
            </div>
            <a href="{{ route('contact-messages.index') }}" class="btn btn-info btn-sm ms-2">
                <i class="bi bi-eye"></i>
            </a>
        </div>
    </div>
    @endif
</div>
@endif

<!-- Commandes site web payées -->
@if($websiteOrdersPaidTotal > 0)
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-success">
            <i class="bi bi-bag-check"></i> {{ __('messages.main_dashboard.website_orders_title') }}
            <span class="badge bg-success ms-2">{{ $websiteOrdersPaidTotal }}</span>
            <small class="text-muted fw-normal ms-2">${{ number_format($websiteOrdersPaidAmount, 2) }}</small>
        </h6>
        <a href="{{ route('website-orders.index') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_all') }}
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.main_dashboard.wo_status') }}</th>
                        <th class="text-center" style="width: 120px;">{{ __('messages.main_dashboard.wo_count') }}</th>
                        <th class="text-end" style="width: 150px;">{{ __('messages.main_dashboard.wo_amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($websiteOrdersByStatus as $row)
                    <tr>
                        <td>
                            <span class="badge bg-{{ \App\Models\WebsiteOrder::statusBadgeClass($row->status) }}">
                                {{ __('messages.website_order.status_' . $row->status) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold">{{ $row->count }}</span>
                        </td>
                        <td class="text-end fw-bold">${{ number_format($row->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>Total</th>
                        <th class="text-center">{{ $websiteOrdersPaidTotal }}</th>
                        <th class="text-end">${{ number_format($websiteOrdersPaidAmount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Alertes commandes spéciales -->
@if(($specialOrdersPending ?? 0) > 0 || ($specialOrdersToProcess ?? 0) > 0)
<div class="row mb-4">
    @if(($specialOrdersPending ?? 0) > 0)
    <div class="col-md-4">
        <div class="alert alert-warning d-flex align-items-center mb-0 h-100" role="alert">
            <i class="bi bi-clipboard2-check fs-3 me-3"></i>
            <div class="flex-grow-1">
                <strong>{{ __('messages.main_dashboard.special_orders_pending') }}</strong><br>
                <span class="fs-5 fw-bold">{{ $specialOrdersPending }}</span>
                <small class="text-muted">{{ __('messages.main_dashboard.awaiting_payment') }}</small>
            </div>
            <a href="{{ route('special-orders.index', ['status' => 'pending', 'payment_status' => 'pending']) }}" class="btn btn-warning btn-sm ms-2">
                <i class="bi bi-eye"></i>
            </a>
        </div>
    </div>
    @endif
    @if(($specialOrdersToProcess ?? 0) > 0)
    <div class="col-md-4">
        <div class="alert alert-info d-flex align-items-center mb-0 h-100" role="alert">
            <i class="bi bi-clipboard2-check fs-3 me-3"></i>
            <div class="flex-grow-1">
                <strong>{{ __('messages.main_dashboard.special_orders_to_process') }}</strong><br>
                <span class="fs-5 fw-bold">{{ $specialOrdersToProcess }}</span>
                <small class="text-muted">{{ __('messages.main_dashboard.paid_to_ship') }}</small>
            </div>
            <a href="{{ route('special-orders.index', ['payment_status' => 'paid']) }}" class="btn btn-info btn-sm ms-2">
                <i class="bi bi-eye"></i>
            </a>
        </div>
    </div>
    @endif
</div>
@endif

<!-- Cartes KPI originales -->
<div class="row">
    <!-- Carte KPI: Factures à payer -->
    <div class="col-md-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body d-flex flex-column justify-content-between h-100">
                <div>
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size:1.5em;">
                        {{ __('messages.main_dashboard.invoices_to_pay') }}
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:5em;">
                        {{ $invoicesToPayCount ?? 0 }}
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:1em">
                        {{ __('messages.main_dashboard.total_amount') }} : ${{ number_format($invoicesToPayTotal ?? 0, 2) }}
                    </div>
                </div>
                <div class="text-end">
                    <a class="btn btn-success" href="{{ route('financial.overview') }}">{{ __('messages.main_dashboard.view') }}</a>
                </div>
            </div>
        </div>
    </div>

    @php
        $storeColors = ['warning', 'success', 'info', 'primary', 'danger'];
    @endphp
    @foreach ($storeRevenues as $index => $sr)
        @php
            $color = $storeColors[$index % count($storeColors)];
            $cardId = 'store-' . $sr['store']->id;
        @endphp
    <!-- Carte KPI: C.A. {{ $sr['store']->name }} -->
    <div class="col-md-4">
        <div class="card border-left-{{ $color }} shadow h-100 py-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase" style="font-size:1.5em;">
                        {{ $sr['store']->name }}
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-primary btn-sm active" onclick="toggleCardPeriod('{{ $cardId }}', 'daily', this)">{{ __('messages.main_dashboard.day') }}</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleCardPeriod('{{ $cardId }}', 'monthly', this)">{{ __('messages.main_dashboard.month') }}</button>
                    </div>
                </div>
                <div id="{{ $cardId }}-daily">
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:5em;">${{ number_format($sr['daily_revenue'] ?? 0, 2) }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:1em">
                        {{ __('messages.main_dashboard.sales_count') }}: {{ $sr['daily_count'] ?? 0 }}
                    </div>
                    <div class="mt-2">
                        <a href="{{ route('dashboard.daily-sales', ['store' => $sr['store']->id, 'date' => $selectedDate->format('Y-m-d')]) }}" class="btn btn-sm btn-{{ $color }}">
                            <i class="bi bi-eye"></i> {{ __('messages.main_dashboard.view_sales') }}
                        </a>
                    </div>
                </div>
                <div id="{{ $cardId }}-monthly" style="display: none;">
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:5em;">${{ number_format($sr['monthly_revenue'] ?? 0, 2) }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:1em">
                        {{ __('messages.main_dashboard.sales_count') }}: {{ $sr['monthly_count'] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Graphiques côte à côte -->
<div class="row mt-4">
    <!-- Graphique à barres -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header">
                {{ __('messages.main_dashboard.invoices_by_status') }}
            </div>
            <div class="card-body">
                <canvas id="invoicesChart" width="400" height="150"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphique en ligne -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header">
                {{ __('messages.main_dashboard.monthly_revenue') }}
            </div>
            <div class="card-body">
                <canvas id="revenueChart" width="400" height="150"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Changement de date du dashboard
    function changeDashboardDate(date) {
        window.location.href = '{{ route("dashboard") }}?date=' + date;
    }
    window.changeDashboardDate = changeDashboardDate;
    // Graphique à barres - Factures par statut
    const ctxBar = document.getElementById('invoicesChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ["{{ __('messages.main_dashboard.to_pay') }}", "{{ __('messages.main_dashboard.paid') }}"],
            datasets: [{
                label: "{{ __('messages.main_dashboard.invoice_count') }}",
                data: [{{ $invoicesByStatus['to_pay'] ?? 0 }}, {{ $invoicesByStatus['paid'] ?? 0 }}],
                backgroundColor: [
                    'rgba(78, 115, 223, 0.5)',
                    'rgba(28, 200, 138, 0.5)'
                ],
                borderColor: [
                    'rgba(78, 115, 223, 1)',
                    'rgba(28, 200, 138, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Graphique en ligne - C.A. mensuel (Total + 1 dataset par shop)
    @php
        $shopPalette = [
            [246, 194, 62],  // warning
            [28, 200, 138],  // success
            [54, 185, 204],  // info
            [78, 115, 223],  // primary
            [231, 74, 59],   // danger
        ];
    @endphp
    const ctxLine = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthLabels ?? []) !!},
            datasets: [
                {
                    label: 'Total',
                    data: {!! json_encode($monthlyRevenueTotal ?? []) !!},
                    fill: false,
                    borderColor: 'rgba(78, 115, 223, 1)',
                    backgroundColor: 'rgba(78, 115, 223, 0.5)',
                    tension: 0.3,
                    borderWidth: 3
                }
                @foreach ($shops as $index => $shop)
                    @php
                        $rgb = $shopPalette[$index % count($shopPalette)];
                        $data = $monthlyRevenuePerStore[$shop->id] ?? [];
                    @endphp
                ,{
                    label: {!! json_encode($shop->name) !!},
                    data: {!! json_encode($data) !!},
                    fill: false,
                    borderColor: 'rgba({{ $rgb[0] }}, {{ $rgb[1] }}, {{ $rgb[2] }}, 1)',
                    backgroundColor: 'rgba({{ $rgb[0] }}, {{ $rgb[1] }}, {{ $rgb[2] }}, 0.5)',
                    tension: 0.3,
                    borderWidth: 2
                }
                @endforeach
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: false } }
        }
    });

    // Toggle entre revenus journaliers et mensuels par carte
    function toggleCardPeriod(cardId, period, clickedBtn) {
        const dailyEl = document.getElementById(cardId + '-daily');
        const monthlyEl = document.getElementById(cardId + '-monthly');
        const btnGroup = clickedBtn.parentElement;
        const buttons = btnGroup.querySelectorAll('button');

        if (period === 'daily') {
            dailyEl.style.display = 'block';
            monthlyEl.style.display = 'none';
        } else {
            dailyEl.style.display = 'none';
            monthlyEl.style.display = 'block';
        }

        // Mettre à jour les styles des boutons
        buttons.forEach(btn => {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        });
        clickedBtn.classList.remove('btn-outline-primary');
        clickedBtn.classList.add('btn-primary', 'active');
    }

    // Exposer la fonction globalement
    window.toggleCardPeriod = toggleCardPeriod;

    // Toggle icône et texte du bouton des alertes produits
    const productAlertsContent = document.getElementById('productAlertsContent');
    if (productAlertsContent) {
        productAlertsContent.addEventListener('show.bs.collapse', function () {
            const btn = document.querySelector('[data-bs-target="#productAlertsContent"]');
            btn.innerHTML = '<i class="bi bi-chevron-up"></i> {{ __('messages.main_dashboard.hide') }}';
        });
        productAlertsContent.addEventListener('hide.bs.collapse', function () {
            const btn = document.querySelector('[data-bs-target="#productAlertsContent"]');
            btn.innerHTML = '<i class="bi bi-chevron-down"></i> {{ __('messages.main_dashboard.show') }}';
        });
    }
</script>
@endpush
