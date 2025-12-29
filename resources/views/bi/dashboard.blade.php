@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mt-4">{{ __('messages.bi.title') }}</h1>
    <form method="GET" class="d-flex align-items-center gap-2">
        <label for="period" class="form-label mb-0">{{ __('messages.bi.period') }}:</label>
        <select name="period" id="period" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            <option value="week" {{ $period === 'week' ? 'selected' : '' }}>{{ __('messages.bi.week') }}</option>
            <option value="month" {{ $period === 'month' ? 'selected' : '' }}>{{ __('messages.bi.month') }}</option>
            <option value="quarter" {{ $period === 'quarter' ? 'selected' : '' }}>{{ __('messages.bi.quarter') }}</option>
            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>{{ __('messages.bi.year') }}</option>
            <option value="all" {{ $period === 'all' ? 'selected' : '' }}>{{ __('messages.bi.all_time') }}</option>
        </select>
    </form>
</div>

{{-- KPI Cards Row --}}
<div class="row mb-4">
    {{-- Total Revenue --}}
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    {{ __('messages.bi.total_revenue') }}
                </div>
                <div class="h4 mb-0 font-weight-bold text-gray-800">
                    ${{ number_format($totalRevenue, 2) }}
                </div>
                @if($revenueGrowth != 0)
                <div class="mt-2 {{ $revenueGrowth > 0 ? 'text-success' : 'text-danger' }}">
                    <i class="bi {{ $revenueGrowth > 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                    {{ number_format(abs($revenueGrowth), 1) }}% {{ __('messages.bi.vs_previous') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Total Sales --}}
    <div class="col-md-3">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                    {{ __('messages.bi.total_sales') }}
                </div>
                <div class="h4 mb-0 font-weight-bold text-gray-800">
                    {{ number_format($totalSales) }}
                </div>
                @if($salesGrowth != 0)
                <div class="mt-2 {{ $salesGrowth > 0 ? 'text-success' : 'text-danger' }}">
                    <i class="bi {{ $salesGrowth > 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                    {{ number_format(abs($salesGrowth), 1) }}% {{ __('messages.bi.vs_previous') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Average Basket --}}
    <div class="col-md-3">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                    {{ __('messages.bi.average_basket') }}
                </div>
                <div class="h4 mb-0 font-weight-bold text-gray-800">
                    ${{ number_format($averageBasket, 2) }}
                </div>
                <div class="mt-2 text-muted small">
                    {{ number_format($totalItemsSold) }} {{ __('messages.bi.items_sold') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Total Margin --}}
    <div class="col-md-3">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                    {{ __('messages.bi.total_margin') }}
                </div>
                <div class="h4 mb-0 font-weight-bold text-gray-800">
                    ${{ number_format($totalMargin, 2) }}
                </div>
                @if($totalRevenue > 0)
                <div class="mt-2 text-muted small">
                    {{ number_format(($totalMargin / $totalRevenue) * 100, 1) }}% {{ __('messages.bi.margin_rate') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Revenue by Store --}}
<div class="row mb-4">
    @foreach($stores as $store)
    <div class="col-md-{{ 12 / count($stores) }}">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-shop"></i> {{ $store->name }}
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4">
                        <div class="text-xs text-muted text-uppercase">{{ __('messages.bi.revenue') }}</div>
                        <div class="h5 font-weight-bold">${{ number_format($totalRevenueByStore[$store->id] ?? 0, 2) }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-xs text-muted text-uppercase">{{ __('messages.bi.avg_basket') }}</div>
                        <div class="h5 font-weight-bold">${{ number_format($averageBasketByStore[$store->id] ?? 0, 2) }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-xs text-muted text-uppercase">{{ __('messages.bi.avg_items_per_sale') }}</div>
                        <div class="h5 font-weight-bold">{{ number_format($averageItemsPerSaleByStore[$store->id] ?? 0, 1) }}</div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <div class="text-xs text-muted text-uppercase">{{ __('messages.bi.margin') }}</div>
                        <div class="h5 font-weight-bold">${{ number_format($marginByStore[$store->id] ?? 0, 2) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-xs text-muted text-uppercase">{{ __('messages.bi.stock_value') }}</div>
                        <div class="h5 font-weight-bold">${{ number_format($stockValueByStore[$store->id] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Charts Row --}}
<div class="row mb-4">
    {{-- Monthly Evolution --}}
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">{{ __('messages.bi.monthly_evolution') }}</h6>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="120"></canvas>
            </div>
        </div>
    </div>

    {{-- Payment Distribution --}}
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">{{ __('messages.bi.payment_methods') }}</h6>
            </div>
            <div class="card-body">
                <canvas id="paymentChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Top Products Section --}}
<div class="row mb-4">
    {{-- Top Products All Stores --}}
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-trophy"></i> {{ __('messages.bi.top_products_all') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('messages.bi.product') }}</th>
                                <th>{{ __('messages.bi.brand') }}</th>
                                <th class="text-end">{{ __('messages.bi.quantity') }}</th>
                                <th class="text-end">{{ __('messages.bi.revenue') }}</th>
                                <th class="text-end">{{ __('messages.bi.margin') }}</th>
                                <th class="text-end">{{ __('messages.bi.margin_percent') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProductsAll as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <a href="{{ route('products.show', $item['product']->id) }}">
                                        {{ $item['product']->name[app()->getLocale()] ?? reset($item['product']->name) }}
                                    </a>
                                </td>
                                <td>{{ $item['product']->brand?->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($item['quantity']) }}</td>
                                <td class="text-end">${{ number_format($item['revenue'], 2) }}</td>
                                <td class="text-end {{ $item['margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    ${{ number_format($item['margin'], 2) }}
                                </td>
                                <td class="text-end">
                                    <span class="badge {{ $item['margin_percent'] >= 30 ? 'bg-success' : ($item['margin_percent'] >= 15 ? 'bg-warning' : 'bg-danger') }}">
                                        {{ number_format($item['margin_percent'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('messages.bi.no_data') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top Products by Store --}}
<div class="row mb-4">
    @foreach($stores as $store)
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-trophy"></i> {{ __('messages.bi.top_products') }} - {{ $store->name }}
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('messages.bi.product') }}</th>
                                <th class="text-end">{{ __('messages.bi.qty') }}</th>
                                <th class="text-end">{{ __('messages.bi.revenue') }}</th>
                                <th class="text-end">{{ __('messages.bi.margin') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProductsByStore[$store->id] ?? [] as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="text-truncate" style="max-width: 150px;">
                                    {{ $item['product']->name[app()->getLocale()] ?? reset($item['product']->name) }}
                                </td>
                                <td class="text-end">{{ number_format($item['quantity']) }}</td>
                                <td class="text-end">${{ number_format($item['revenue'], 2) }}</td>
                                <td class="text-end {{ $item['margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    ${{ number_format($item['margin'], 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('messages.bi.no_data') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Top Resellers --}}
<div class="row mb-4">
    {{-- By Quantity --}}
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-people"></i> {{ __('messages.bi.top_resellers_qty') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('messages.bi.reseller') }}</th>
                                <th class="text-end">{{ __('messages.bi.quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topResellersByQuantity as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if($item['reseller'])
                                    <a href="{{ route('resellers.show', $item['reseller']->id) }}">
                                        {{ $item['reseller']->name }}
                                    </a>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($item['quantity']) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">{{ __('messages.bi.no_data') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- By Revenue --}}
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-currency-dollar"></i> {{ __('messages.bi.top_resellers_revenue') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('messages.bi.reseller') }}</th>
                                <th class="text-end">{{ __('messages.bi.revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topResellersByRevenue as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if($item['reseller'])
                                    <a href="{{ route('resellers.show', $item['reseller']->id) }}">
                                        {{ $item['reseller']->name }}
                                    </a>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td class="text-end">${{ number_format($item['revenue'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">{{ __('messages.bi.no_data') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top Brands & Categories --}}
<div class="row mb-4">
    {{-- Top Brands --}}
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-award"></i> {{ __('messages.bi.top_brands') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('messages.bi.brand') }}</th>
                                <th class="text-end">{{ __('messages.bi.quantity') }}</th>
                                <th class="text-end">{{ __('messages.bi.revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topBrands as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item['brand']?->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($item['quantity']) }}</td>
                                <td class="text-end">${{ number_format($item['revenue'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ __('messages.bi.no_data') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Categories --}}
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-tags"></i> {{ __('messages.bi.top_categories') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('messages.bi.category') }}</th>
                                <th class="text-end">{{ __('messages.bi.quantity') }}</th>
                                <th class="text-end">{{ __('messages.bi.revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topCategories as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item['name'] }}</td>
                                <td class="text-end">{{ number_format($item['quantity']) }}</td>
                                <td class="text-end">${{ number_format($item['revenue'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ __('messages.bi.no_data') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Stock Value Summary --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-box-seam"></i> {{ __('messages.bi.stock_summary') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center border-end">
                        <div class="text-xs text-muted text-uppercase mb-2">{{ __('messages.bi.total_stock_value') }}</div>
                        <div class="h3 font-weight-bold text-primary">${{ number_format($stockValue, 2) }}</div>
                    </div>
                    @foreach($stores as $store)
                    <div class="col-md-{{ 8 / count($stores) }} text-center">
                        <div class="text-xs text-muted text-uppercase mb-2">{{ $store->name }}</div>
                        <div class="h4 font-weight-bold">${{ number_format($stockValueByStore[$store->id] ?? 0, 2) }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Monthly Evolution Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyData = @json($monthlyEvolution);

    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                {
                    label: '{{ __('messages.bi.revenue') }}',
                    data: monthlyData.map(d => d.revenue),
                    borderColor: 'rgba(78, 115, 223, 1)',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: '{{ __('messages.bi.sales_count') }}',
                    data: monthlyData.map(d => d.sales),
                    borderColor: 'rgba(28, 200, 138, 1)',
                    backgroundColor: 'rgba(28, 200, 138, 0.5)',
                    fill: false,
                    tension: 0.3,
                    type: 'bar',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: '{{ __('messages.bi.revenue') }} ($)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: '{{ __('messages.bi.sales_count') }}'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // Payment Distribution Chart
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    const paymentData = @json($paymentDistribution);
    const paymentLabels = Object.keys(paymentData);
    const paymentValues = paymentLabels.map(k => paymentData[k].total);

    new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: paymentLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
            datasets: [{
                data: paymentValues,
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)',
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(231, 74, 59, 0.8)',
                    'rgba(54, 185, 204, 0.8)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `$${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
</script>
@endpush
