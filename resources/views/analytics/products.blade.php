@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title">
        <i class="bi bi-bag"></i> {{ __('messages.analytics.products.title') }}
    </h1>
    <p class="text-muted">{{ __('messages.analytics.products.description') }}</p>

    @include('analytics.partials.period-picker', ['extraFields' => ['filter' => $filter, 'sort' => $sort]])

    {{-- Filter + sort --}}
    <form method="GET" class="row g-2 mb-3">
        <input type="hidden" name="period" value="{{ request('period', '30d') }}">
        @if(request('start')) <input type="hidden" name="start" value="{{ request('start') }}"> @endif
        @if(request('end')) <input type="hidden" name="end" value="{{ request('end') }}"> @endif
        <div class="col-md-4">
            <label class="form-label small">{{ __('messages.analytics.products.filter') }}</label>
            <select name="filter" class="form-select" onchange="this.form.submit()">
                <option value="" @selected($filter === '')>{{ __('messages.analytics.products.filter_all') }}</option>
                <option value="viewed_not_purchased" @selected($filter === 'viewed_not_purchased')>{{ __('messages.analytics.products.filter_viewed_not_purchased') }}</option>
                <option value="purchased_not_viewed" @selected($filter === 'purchased_not_viewed')>{{ __('messages.analytics.products.filter_purchased_not_viewed') }}</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">{{ __('messages.analytics.products.sort_by') }}</label>
            <select name="sort" class="form-select" onchange="this.form.submit()">
                <option value="views"          @selected($sort === 'views')>{{ __('messages.analytics.products.views') }}</option>
                <option value="unique_viewers" @selected($sort === 'unique_viewers')>{{ __('messages.analytics.products.unique_viewers') }}</option>
                <option value="cart_adds"      @selected($sort === 'cart_adds')>{{ __('messages.analytics.products.cart_adds') }}</option>
                <option value="purchases"      @selected($sort === 'purchases')>{{ __('messages.analytics.products.purchases') }}</option>
                <option value="units_sold"     @selected($sort === 'units_sold')>{{ __('messages.analytics.products.units_sold') }}</option>
                <option value="revenue"        @selected($sort === 'revenue')>{{ __('messages.analytics.products.revenue') }}</option>
            </select>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.analytics.products.product') }}</th>
                        <th class="text-end">{{ __('messages.analytics.products.views') }}</th>
                        <th class="text-end">{{ __('messages.analytics.products.unique_viewers') }}</th>
                        <th class="text-end">{{ __('messages.analytics.products.cart_adds') }}</th>
                        <th class="text-end">{{ __('messages.analytics.products.purchases') }}</th>
                        <th class="text-end">{{ __('messages.analytics.products.units_sold') }}</th>
                        <th class="text-end">{{ __('messages.analytics.products.revenue') }}</th>
                        <th class="text-end">{{ __('messages.analytics.products.view_to_cart') }}</th>
                        <th class="text-end">{{ __('messages.analytics.products.cart_to_purchase') }}</th>
                        <th class="text-end">{{ __('messages.analytics.products.view_to_purchase') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td>
                                <a href="{{ route('products.edit', $r->product_id) }}" target="_blank" class="text-decoration-none">
                                    {{ $r->product_name }}
                                </a>
                                @if($r->product_ean)
                                    <small class="text-muted d-block">{{ $r->product_ean }}</small>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($r->views) }}</td>
                            <td class="text-end">{{ number_format($r->unique_viewers) }}</td>
                            <td class="text-end">{{ number_format($r->cart_adds) }}</td>
                            <td class="text-end">{{ number_format($r->purchases) }}</td>
                            <td class="text-end">{{ number_format($r->units_sold) }}</td>
                            <td class="text-end fw-bold">{{ number_format($r->revenue, 2) }}$</td>
                            <td class="text-end">{{ $r->view_to_cart }}%</td>
                            <td class="text-end">{{ $r->cart_to_purchase }}%</td>
                            <td class="text-end">
                                <span class="badge {{ $r->view_to_purchase >= 2 ? 'bg-success' : ($r->view_to_purchase >= 0.5 ? 'bg-warning' : 'bg-secondary') }}">{{ $r->view_to_purchase }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">{{ __('messages.analytics.overview.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
