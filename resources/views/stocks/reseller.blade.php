@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.menu.stock_overview_reseller') }}</h1>

    <!-- Sélecteur de reseller -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('stocks.reseller') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.resellers.select_reseller') }}</label>
                    <select name="reseller_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- {{ __('messages.btn.select') }} --</option>
                        @foreach($resellers as $r)
                            @php
                                $typeLabel = $r->type === 'consignment' ? __('messages.resellers.type_consignment') : __('messages.resellers.type_buyer');
                            @endphp
                            <option value="{{ $r->id }}" {{ $selectedResellerId == $r->id ? 'selected' : '' }}>
                                {{ $r->name }} ({{ $typeLabel }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($selectedResellerId)
                <div class="col-md-4">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                           placeholder="{{ __('messages.stock_value.search_placeholder') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> {{ __('messages.search') }}
                    </button>
                </div>
                @if(request('q'))
                <div class="col-md-2">
                    <a href="{{ route('stocks.reseller', ['reseller_id' => $selectedResellerId]) }}" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> {{ __('messages.reset') }}
                    </a>
                </div>
                @endif
                @endif
            </form>
        </div>
    </div>

    @if($selectedReseller)
        <!-- Version Desktop -->
        <div class="d-none d-md-block">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th style="min-width:180px;">
                            <form action="{{ route('stocks.reseller') }}" method="GET" id="brandFilterForm">
                                <input type="hidden" name="reseller_id" value="{{ $selectedResellerId }}">
                                @if(request('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}">
                                @endif
                                @if(request('perPage'))
                                    <input type="hidden" name="perPage" value="{{ request('perPage') }}">
                                @endif
                                <select name="brand_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">{{ __('messages.all_brands') }}</option>
                                    <option value="none" {{ request('brand_id') === 'none' ? 'selected' : '' }}>
                                        {{ __('messages.no_brand') }}
                                    </option>
                                    @foreach($brands as $b)
                                        <option value="{{ $b->id }}" {{ (string)$b->id === request('brand_id') ? 'selected' : '' }}>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </th>
                        <th class="text-center">{{ __('messages.resellers.current_stock') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        @php
                            $qty = $stock[$p->id] ?? 0;
                        @endphp
                        <tr>
                            <td>{{ $p->ean }}</td>
                            <td>{{ $p->name[app()->getLocale()] ?? reset($p->name) }}</td>
                            <td>{{ $p->brand?->name ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $qty }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">{{ __('messages.resellers.no_stock') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->hasPages())
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span>{{ __('messages.pagination.show') }}</span>
                    <select class="form-select form-select-sm" style="width:auto;" onchange="window.location.href=this.value">
                        @foreach([25, 50, 100] as $option)
                            <option value="{{ request()->fullUrlWithQuery(['perPage' => $option, 'page' => 1]) }}" {{ request('perPage', 100) == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    <span>{{ __('messages.pagination.rows') }}</span>
                </div>
                <div>
                    {{ $products->links() }}
                </div>
            </div>
            @endif
        </div>

        <!-- Version Mobile -->
        <div class="d-md-none">
            <!-- Filtre marque mobile -->
            <div class="mb-3">
                <form action="{{ route('stocks.reseller') }}" method="GET">
                    <input type="hidden" name="reseller_id" value="{{ $selectedResellerId }}">
                    @if(request('q'))
                        <input type="hidden" name="q" value="{{ request('q') }}">
                    @endif
                    @if(request('perPage'))
                        <input type="hidden" name="perPage" value="{{ request('perPage') }}">
                    @endif
                    <select name="brand_id" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('messages.all_brands') }}</option>
                        <option value="none" {{ request('brand_id') === 'none' ? 'selected' : '' }}>
                            {{ __('messages.no_brand') }}
                        </option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" {{ (string)$b->id === request('brand_id') ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="row">
                @forelse($products as $p)
                    @php
                        $qty = $stock[$p->id] ?? 0;
                    @endphp
                <div class="col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <h5 class="card-title mb-1">
                                {{ $p->name[app()->getLocale()] ?? reset($p->name) }}
                            </h5>
                            <p class="mb-1"><strong>EAN:</strong> {{ $p->ean }}</p>
                            @if($p->brand)
                                <p class="mb-1 text-muted"><small>{{ $p->brand->name }}</small></p>
                            @endif
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span>{{ __('messages.resellers.current_stock') }}</span>
                                <span class="badge bg-info">{{ $qty }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-info">{{ __('messages.resellers.no_stock') }}</div>
                </div>
                @endforelse

                @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->hasPages())
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span>{{ __('messages.pagination.show') }}</span>
                        <select class="form-select form-select-sm" style="width:auto;" onchange="window.location.href=this.value">
                            @foreach([25, 50, 100] as $option)
                                <option value="{{ request()->fullUrlWithQuery(['perPage' => $option, 'page' => 1]) }}" {{ request('perPage', 100) == $option ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                        <span>{{ __('messages.pagination.rows') }}</span>
                    </div>
                    <div>
                        {{ $products->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-info">
            {{ __('messages.resellers.select_reseller_hint') }}
        </div>
    @endif
</div>
@endsection
