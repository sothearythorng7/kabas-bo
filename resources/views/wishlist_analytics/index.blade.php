@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">
        <i class="bi bi-heart-fill text-danger"></i> {{ __('messages.wishlist_analytics.title') }}
    </h1>

    <p class="text-muted">{{ __('messages.wishlist_analytics.description') }}</p>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.wishlist_analytics.total_items') }}</div>
                    <div class="display-6 fw-bold text-primary">{{ number_format($totals['items']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.wishlist_analytics.unique_customers') }}</div>
                    <div class="display-6 fw-bold text-info">{{ number_format($totals['customers']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.wishlist_analytics.unique_products') }}</div>
                    <div class="display-6 fw-bold text-success">{{ number_format($totals['products']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.wishlist_analytics.unique_gift_boxes') }}</div>
                    <div class="display-6 fw-bold text-warning">{{ number_format($totals['gift_boxes']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('wishlist-analytics.index') }}" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control"
                   placeholder="{{ __('messages.wishlist_analytics.search_placeholder') }}"
                   value="{{ $search }}">
        </div>
        <div class="col-md-3">
            <select name="stock" class="form-select" onchange="this.form.submit()">
                <option value="">{{ __('messages.wishlist_analytics.all_stock') }}</option>
                <option value="in_stock" @selected($stockFilter === 'in_stock')>{{ __('messages.wishlist_analytics.in_stock') }}</option>
                <option value="out_of_stock" @selected($stockFilter === 'out_of_stock')>{{ __('messages.wishlist_analytics.out_of_stock') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-search"></i> {{ __('messages.btn.filter') ?? 'Filter' }}
            </button>
            @if($search || $stockFilter)
                <a href="{{ route('wishlist-analytics.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>
    </form>

    {{-- Products table --}}
    <div class="card mb-4">
        <div class="card-header bg-light">
            <i class="bi bi-box-seam"></i> {{ __('messages.wishlist_analytics.products_section') }}
            <span class="badge bg-secondary ms-1">{{ $products->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 60px;"></th>
                            <th>{{ __('messages.wishlist_analytics.product') }}</th>
                            <th class="text-end">{{ __('messages.wishlist_analytics.price') }}</th>
                            <th class="text-center">{{ __('messages.wishlist_analytics.warehouse_stock') }}</th>
                            <th class="text-center">{{ __('messages.wishlist_analytics.stock_status') }}</th>
                            <th class="text-center">{{ __('messages.wishlist_analytics.wishlist_count') }}</th>
                            <th>{{ __('messages.wishlist_analytics.last_added') }}</th>
                            <th style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $p)
                            <tr>
                                <td>
                                    @if($p->image)
                                        <img src="{{ asset('storage/'.$p->image) }}" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px;">
                                    @else
                                        <div style="width: 48px; height: 48px; background: #f0f0f0; border-radius: 6px;"></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $p->name }}</div>
                                    <div class="small text-muted">ID: {{ $p->id }}</div>
                                </td>
                                <td class="text-end">${{ number_format($p->price, 2) }}</td>
                                <td class="text-center fw-bold {{ $p->stock <= 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $p->stock }}
                                </td>
                                <td class="text-center">
                                    @if($p->in_stock)
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> {{ __('messages.wishlist_analytics.in_stock') }}
                                        </span>
                                        @if($p->allow_overselling && $p->stock <= 0)
                                            <div class="small text-muted mt-1">{{ __('messages.wishlist_analytics.overselling') }}</div>
                                        @endif
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="bi bi-exclamation-triangle"></i> {{ __('messages.wishlist_analytics.out_of_stock') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill" style="font-size: 0.95rem;">
                                        <i class="bi bi-heart-fill"></i> {{ $p->wishlist_count }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ \Carbon\Carbon::parse($p->last_added_at)->format('d/m/Y H:i') }}</small>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('products.edit', $p->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __('messages.wishlist_analytics.view_product') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    {{ __('messages.wishlist_analytics.no_products') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Gift Boxes table --}}
    @if($giftBoxes->count() > 0)
    <div class="card mb-4">
        <div class="card-header bg-light">
            <i class="bi bi-gift"></i> {{ __('messages.wishlist_analytics.gift_boxes_section') }}
            <span class="badge bg-secondary ms-1">{{ $giftBoxes->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 60px;"></th>
                            <th>{{ __('messages.wishlist_analytics.gift_box') }}</th>
                            <th class="text-end">{{ __('messages.wishlist_analytics.price') }}</th>
                            <th class="text-center">{{ __('messages.wishlist_analytics.wishlist_count') }}</th>
                            <th>{{ __('messages.wishlist_analytics.last_added') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($giftBoxes as $gb)
                            <tr>
                                <td>
                                    @if($gb->image)
                                        <img src="{{ asset('storage/'.$gb->image) }}" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px;">
                                    @else
                                        <div style="width: 48px; height: 48px; background: #f0f0f0; border-radius: 6px;"></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $gb->name }}</div>
                                    <div class="small text-muted">ID: {{ $gb->id }}</div>
                                </td>
                                <td class="text-end">${{ number_format($gb->price, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill" style="font-size: 0.95rem;">
                                        <i class="bi bi-heart-fill"></i> {{ $gb->wishlist_count }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ \Carbon\Carbon::parse($gb->last_added_at)->format('d/m/Y H:i') }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Additions per day chart --}}
    <div class="card">
        <div class="card-header bg-light">
            <i class="bi bi-graph-up"></i> {{ __('messages.wishlist_analytics.chart_title') }}
        </div>
        <div class="card-body">
            <canvas id="wishlistAdditionsChart" height="80"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const el = document.getElementById('wishlistAdditionsChart');
    if (!el) return;
    const daily = @json($daily);
    const labels = daily.map(d => {
        const dt = new Date(d.date);
        return dt.toLocaleDateString(undefined, { day: '2-digit', month: '2-digit' });
    });
    const counts = daily.map(d => d.count);
    new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: @json(__('messages.wishlist_analytics.additions')),
                data: counts,
                backgroundColor: 'rgba(220, 53, 69, 0.6)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
@endsection
