@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.resellers.title_edit') }}: {{ $reseller->name }}</h1>

    @php
        $resellerType = $reseller->type ?? 'buyer';
    @endphp

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="resellerTabs" role="tablist">
        @if($resellerType !== 'shop')
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                <i class="bi bi-info-circle"></i> {{ __('messages.resellers.info') }}
            </button>
        </li>
        @endif
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $resellerType === 'shop' ? 'active' : '' }}" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab" aria-controls="contacts" aria-selected="{{ $resellerType === 'shop' ? 'true' : 'false' }}">
                {{ __('messages.resellers.contacts') }}
            </button>
        </li>

        @if(in_array($resellerType, ['consignment', 'shop']))
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                {{ __('messages.product.products') }}
                <span class="badge bg-{{ ($products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products->total() : $products->count()) > 0 ? 'success' : 'danger' }}">
                    {{ $products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products->total() : $products->count() }}
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns" type="button" role="tab" aria-controls="returns" aria-selected="false">
                {{ __('messages.resellers.returns') }}
                <span class="badge bg-{{ (isset($returns) && $returns->count() > 0) ? 'warning' : 'secondary' }}">
                    {{ isset($returns) ? $returns->count() : 0 }}
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab" aria-controls="reports" aria-selected="false">
                {{ __('messages.resellers.sale_reports') }}
                <span class="badge bg-{{ ($salesReports instanceof \Illuminate\Pagination\LengthAwarePaginator ? $salesReports->total() : $salesReports->count()) > 0 ? 'primary' : 'secondary' }}">
                    {{ $salesReports instanceof \Illuminate\Pagination\LengthAwarePaginator ? $salesReports->total() : $salesReports->count() }}
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="anomalies-tab" data-bs-toggle="tab" data-bs-target="#anomalies" type="button" role="tab" aria-controls="anomalies" aria-selected="false">
                {{ __('messages.resellers.disputes') }}
                @if(($pendingDisputesCount ?? 0) > 0)
                    <span class="badge bg-danger">{{ $pendingDisputesCount }}</span>
                @endif
            </button>
        </li>
        @endif

        <li class="nav-item" role="presentation">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="true">
                {{ __('messages.resellers.deliveries') }}
                <span class="badge bg-{{ ($deliveries instanceof \Illuminate\Pagination\LengthAwarePaginator ? $deliveries->total() : $deliveries->count()) > 0 ? 'primary' : 'secondary' }}">
                    {{ $deliveries instanceof \Illuminate\Pagination\LengthAwarePaginator ? $deliveries->total() : $deliveries->count() }}
                </span>
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="resellerTabsContent">

        {{-- Onglet Informations --}}
        @if($resellerType !== 'shop')
        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-building"></i> {{ __('messages.resellers.billing_info') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('resellers.update-info', $reseller->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('messages.resellers.company_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $reseller->name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tax_id" class="form-label">{{ __('messages.resellers.tax_id') }}</label>
                                    <input type="text" class="form-control" id="tax_id" name="tax_id" value="{{ old('tax_id', $reseller->tax_id) }}" placeholder="VAT / Tax ID">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label">{{ __('messages.resellers.address') }}</label>
                                    <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $reseller->address) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="address2" class="form-label">{{ __('messages.resellers.address2') }}</label>
                                    <input type="text" class="form-control" id="address2" name="address2" value="{{ old('address2', $reseller->address2) }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="city" class="form-label">{{ __('messages.resellers.city') }}</label>
                                    <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $reseller->city) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">{{ __('messages.resellers.postal_code') }}</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code', $reseller->postal_code) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="country" class="form-label">{{ __('messages.resellers.country') }}</label>
                                    <input type="text" class="form-control" id="country" name="country" value="{{ old('country', $reseller->country) }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">{{ __('messages.resellers.phone') }}</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $reseller->phone) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('messages.resellers.email') }}</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $reseller->email) }}">
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> {{ __('messages.btn.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Onglet Contacts --}}
        <div class="tab-pane fade {{ $resellerType === 'shop' ? 'show active' : '' }}" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>{{ __('messages.resellers.contacts') }}</h3>
                @if($resellerType !== 'shop')
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addContactModal">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                </button>
                @endif
            </div>

            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th></th> {{-- Dropdown --}}
                        <th>{{ __('messages.resellers.name') }}</th>
                        <th>{{ __('messages.resellers.email') }}</th>
                        <th>{{ __('messages.resellers.phone') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reseller->contacts as $contact)
                        <tr>
                            <td class="text-center">
                                @if($resellerType !== 'shop')
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownContact{{ $contact->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownContact{{ $contact->id }}">
                                        <li>
                                            <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editContactModal{{ $contact->id }}">
                                                <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                            </button>
                                        </li>
                                        <li>
                                            <form action="{{ route('resellers.contacts.destroy', [$reseller->id ?? 0, $contact]) }}" method="POST" onsubmit="return confirm('{{ __('messages.resellers.confirm_delete_contact') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="dropdown-item text-danger" type="submit">
                                                    <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                                @endif
                            </td>
                            <td>{{ $contact->name }}</td>
                            <td>{{ $contact->email }}</td>
                            <td>{{ $contact->phone }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Modal Ajouter Contact --}}
            @if($resellerType !== 'shop')
            <div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('resellers.contacts.store', $reseller->id ?? 0) }}" method="POST">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addContactModalLabel">{{ __('messages.resellers.add_contact') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('messages.resellers.name') }}</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('messages.resellers.email') }}</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">{{ __('messages.resellers.phone') }}</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.close') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('messages.btn.save') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Onglet Produits --}}
        @if(in_array($resellerType, ['consignment', 'shop']))
        <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
            {{-- Formulaire de recherche --}}
            <div class="mb-3">
                <form action="{{ route('resellers.show', $reseller->id) }}" method="GET" class="row g-2">
                    <input type="hidden" name="tab" value="products">
                    @if(request('brand_id'))
                        <input type="hidden" name="brand_id" value="{{ request('brand_id') }}">
                    @endif
                    <div class="col-md-6">
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                               placeholder="{{ __('messages.stock_value.search_placeholder') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> {{ __('messages.btn.search') }}
                        </button>
                    </div>
                    @if(request('q') || request('brand_id'))
                    <div class="col-md-2">
                        <a href="{{ route('resellers.show', $reseller->id) }}?tab=products" class="btn btn-secondary w-100">
                            <i class="bi bi-x-circle"></i> {{ __('messages.btn.reset') }}
                        </a>
                    </div>
                    @endif
                </form>
            </div>

            <table class="table table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th>{{ __('messages.product.name') }}</th>
                        <th style="min-width:180px;">
                            <form action="{{ route('resellers.show', $reseller->id) }}" method="GET" id="brandFilterForm">
                                <input type="hidden" name="tab" value="products">
                                @if(request('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}">
                                @endif
                                <select name="brand_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">{{ __('messages.all_brands') }}</option>
                                    <option value="none" {{ request('brand_id') === 'none' ? 'selected' : '' }}>
                                        {{ __('messages.no_brand') }}
                                    </option>
                                    @foreach($brands ?? [] as $b)
                                        <option value="{{ $b->id }}" {{ (string)$b->id === request('brand_id') ? 'selected' : '' }}>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </th>
                        <th class="text-center">{{ __('messages.resellers.stock') }}</th>
                        <th class="text-center">{{ __('messages.resellers.stock_alert') }}</th>
                        @if(!($reseller->is_shop ?? false))
                        <th class="text-center">{{ __('messages.product.price_btob') }}</th>
                        @endif
                        <th class="text-center">{{ __('messages.btn.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        @php
                            $currentStock = $stock[$product->id] ?? 0;
                            $alertThreshold = $alertStocks[$product->id] ?? 0;
                            $customPrice = isset($resellerPrices) ? ($resellerPrices[$product->id] ?? null) : null;
                            $defaultPrice = $product->price_btob ?? $product->price;
                        @endphp
                        <tr>
                            <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                            <td>{{ $product->brand->name ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge {{ $currentStock >= $alertThreshold ? 'bg-success' : 'bg-danger' }}">
                                    {{ $currentStock }}
                                </span>
                            </td>
                            <td class="text-center">{{ $alertThreshold }}</td>
                            @if(!($reseller->is_shop ?? false))
                            <td class="text-center">
                                @if($customPrice !== null)
                                    {{ number_format($customPrice, 2) }} $
                                @else
                                    <span class="text-muted">{{ number_format($defaultPrice, 2) }} $</span>
                                @endif
                            </td>
                            @endif
                            <td class="text-center">
                                @if($reseller->is_shop ?? false)
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editStockModal{{ $product->id }}">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                @else
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editPriceModal{{ $product->id }}">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                @endif
                            </td>
                        </tr>

                        @if($reseller->is_shop ?? false)
                        {{-- Modal pour éditer le stock (shops uniquement) --}}
                        <div class="modal fade" id="editStockModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable">
                                <form action="{{ route('resellers.update-stock', $reseller->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('messages.resellers.edit_stock') }}: {{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.resellers.current_stock') }}</label>
                                                <input type="text" class="form-control" value="{{ $currentStock }}" disabled>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.resellers.new_stock') }}</label>
                                                <input type="number" name="new_stock" class="form-control" value="{{ $currentStock }}" required min="0">
                                                <small class="text-muted">{{ __('messages.resellers.adjustment_note') }}</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.resellers.alert_threshold') }}</label>
                                                <input type="number" name="alert_stock" class="form-control" value="{{ $alertThreshold }}" min="0">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.resellers.adjustment_reason') }}</label>
                                                <textarea name="note" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                            <button type="submit" class="btn btn-primary">{{ __('messages.btn.save') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @else
                        {{-- Modal pour éditer le prix B2B (revendeurs non-shop) --}}
                        <div class="modal fade" id="editPriceModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="{{ route('resellers.update-price', $reseller->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('messages.resellers.edit_price') }}: {{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.product.price_default') }}</label>
                                                <input type="text" class="form-control" value="{{ number_format($defaultPrice, 2) }} $" disabled>
                                                <small class="text-muted">{{ __('messages.resellers.default_price_note') }}</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.product.price_btob') }}</label>
                                                <input type="number" step="0.00001" name="price" class="form-control"
                                                       value="{{ $customPrice ?? $defaultPrice }}" required min="0">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                            <button type="submit" class="btn btn-primary">{{ __('messages.btn.save') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </tbody>
            </table>
            @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $products->appends(['tab' => 'products', 'q' => request('q'), 'brand_id' => request('brand_id')])->links() }}
            @endif
        </div>

        {{-- Onglet Retours --}}
        <div class="tab-pane fade" id="returns" role="tabpanel" aria-labelledby="returns-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>{{ __('messages.resellers.returns') }}</h3>
                <a href="{{ route('resellers.returns.create', $reseller->id) }}" class="btn btn-success">
                    <i class="bi bi-box-arrow-left"></i> {{ __('messages.resellers.create_return') }}
                </a>
            </div>

            <table class="table table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th></th>
                        <th class="text-center">#ID</th>
                        <th>{{ __('messages.resellers.destination_store') }}</th>
                        <th class="text-center">{{ __('messages.resellers.total_items') }}</th>
                        <th>{{ __('messages.resellers.status') }}</th>
                        <th>{{ __('messages.resellers.created_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns ?? [] as $return)
                        @php
                            $statusClass = match($return->status) {
                                'draft' => 'warning',
                                'validated' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td style="width: 1%; white-space: nowrap;" class="text-start">
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownReturn{{ $return->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownReturn{{ $return->id }}">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('resellers.returns.show', [$reseller->id, $return->id]) }}">
                                                <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td class="text-center">{{ $return->id }}</td>
                            <td>{{ $return->destinationStore->name ?? '-' }} ({{ ucfirst($return->destinationStore->type ?? '') }})</td>
                            <td class="text-center">
                                <span class="badge bg-primary">{{ $return->items->sum('quantity') }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($return->status) }}</span>
                            </td>
                            <td>{{ $return->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">{{ __('messages.resellers.no_returns') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if(isset($returns) && $returns instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $returns->appends(['tab' => 'returns'])->links() }}
            @endif
        </div>

        {{-- Onglet Rapports --}}
        <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>{{ __('messages.resellers.sale_reports') }}</h3>
                <a href="{{ route('resellers.reports.create', $reseller->id) }}" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                </a>
            </div>

            <table class="table table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th></th> {{-- Dropdown --}}
                        <th class="text-center">ID</th>
                        <th>{{ __('messages.resellers.period') }}</th>
                        <th class="text-center">{{ __('messages.resellers.total_items') }}</th>
                        <th class="text-center">{{ __('messages.resellers.total_amount') }}</th>
                        <th>{{ __('messages.resellers.invoice_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesReports as $report)
                        @php $invoice = $report->invoice; @endphp
                        <tr>
                            <td style="width: 1%; white-space: nowrap;" class="text-start">
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownReport{{ $report->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownReport{{ $report->id }}">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('resellers.reports.show', [$reseller->id, $report->id]) }}">
                                                <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('resellers.reports.invoice', [$reseller->id, $report->id]) }}">
                                                <i class="bi bi-file-earmark-text-fill"></i> {{ __('messages.btn.invoice') }}
                                            </a>
                                        </li>
                                        @if(!$invoice || $invoice->payments->sum('amount') <= 0)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('resellers.reports.edit', [$reseller->id, $report->id]) }}">
                                                <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('resellers.reports.destroy', [$reseller->id, $report->id]) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('{{ __('messages.resellers.confirm_delete_report') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                            <td class="text-center">{{ $report->id }}</td>
                            <td>
                                @if($report->start_date && $report->end_date)
                                    {{ $report->start_date->format('d/m/Y') }} - {{ $report->end_date->format('d/m/Y') }}
                                @else
                                    {{ $report->created_at->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="text-center">{{ $report->items->sum('quantity_sold') }}</td>
                            <td class="text-center">{{ $invoice ? number_format($invoice->total_amount, 2, ',', ' ') . ' $' : '-' }}</td>
                            <td>
                                @php
                                    $badgeClass = match($invoice->status) {
                                        'unpaid' => 'danger',
                                        'paid' => 'success',
                                        'cancelled' => 'danger',
                                        'partially_paid' => 'warning',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($salesReports instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $salesReports->links() }}
            @endif
        </div>

        {{-- Onglet Litiges / Disputes --}}
        <div class="tab-pane fade" id="anomalies" role="tabpanel" aria-labelledby="anomalies-tab">
            <h3 class="mb-3">{{ __('messages.resellers.disputes') }}</h3>
            <table class="table table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th class="text-center">{{ __('messages.resellers.report_id') }}</th>
                        <th>{{ __('messages.resellers.product') }}</th>
                        <th class="text-center">{{ __('messages.resellers.reported_quantity') }}</th>
                        <th class="text-center">{{ __('messages.resellers.accepted_quantity') }}</th>
                        <th class="text-center">{{ __('messages.resellers.discrepancy') }}</th>
                        <th class="text-center">{{ __('messages.resellers.status') }}</th>
                        <th>{{ __('messages.resellers.date') }}</th>
                        <th>{{ __('messages.btn.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($anomalies as $anomaly)
                        <tr>
                            <td class="text-center">#{{ $anomaly->report_id }}</td>
                            <td>{{ $anomaly->product ? ($anomaly->product->name[app()->getLocale()] ?? reset($anomaly->product->name)) : '-' }}</td>
                            <td class="text-center">{{ $anomaly->reported_quantity ?? $anomaly->quantity }}</td>
                            <td class="text-center">{{ $anomaly->accepted_quantity ?? '-' }}</td>
                            <td class="text-center">
                                @if($anomaly->reported_quantity !== null && $anomaly->accepted_quantity !== null)
                                    <span class="text-danger fw-bold">{{ $anomaly->reported_quantity - $anomaly->accepted_quantity }}</span>
                                @else
                                    {{ $anomaly->quantity }}
                                @endif
                            </td>
                            <td class="text-center">
                                @if($anomaly->status === 'resolved')
                                    <span class="badge bg-success">{{ __('messages.resellers.dispute_resolved') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('messages.order.pending') }}</span>
                                @endif
                            </td>
                            <td>{{ $anomaly->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if($anomaly->status === 'pending')
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#resolveDisputeModal{{ $anomaly->id }}">
                                        <i class="bi bi-check-circle"></i> {{ __('messages.resellers.resolve_dispute') }}
                                    </button>
                                @else
                                    <small class="text-muted">
                                        {{ $anomaly->resolvedBy->name ?? '-' }}
                                        @if($anomaly->resolved_at)
                                            ({{ $anomaly->resolved_at->format('d/m/Y H:i') }})
                                        @endif
                                        @if($anomaly->resolution_note)
                                            <br><em>{{ $anomaly->resolution_note }}</em>
                                        @endif
                                    </small>
                                @endif
                            </td>
                        </tr>

                        @if($anomaly->status === 'pending')
                        {{-- Modal résoudre litige --}}
                        <div class="modal fade" id="resolveDisputeModal{{ $anomaly->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="{{ route('resellers.disputes.resolve', [$reseller->id, $anomaly->id]) }}" method="POST">
                                    @csrf
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('messages.resellers.resolve_dispute') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>
                                                <strong>{{ __('messages.resellers.product') }}:</strong>
                                                {{ $anomaly->product ? ($anomaly->product->name[app()->getLocale()] ?? reset($anomaly->product->name)) : '-' }}
                                            </p>
                                            <p>
                                                <strong>{{ __('messages.resellers.reported_quantity') }}:</strong> {{ $anomaly->reported_quantity ?? $anomaly->quantity }}
                                                &nbsp;|&nbsp;
                                                <strong>{{ __('messages.resellers.accepted_quantity') }}:</strong> {{ $anomaly->accepted_quantity ?? '-' }}
                                            </p>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.resellers.resolution_note') }}</label>
                                                <textarea name="resolution_note" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-check-lg"></i> {{ __('messages.resellers.resolve_dispute') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">{{ __('messages.resellers.no_disputes') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($anomalies instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $anomalies->appends(['tab' => 'anomalies'])->links() }}
            @endif
        </div>
        @endif

        {{-- Onglet Livraisons --}}
        <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>{{ __('messages.resellers.deliveries') }}</h3>
                <a href="{{ route('resellers.deliveries.create', $reseller->id) }}" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                </a>
            </div>

            <table class="table table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th></th> {{-- Dropdown --}}
                        <th class="text-center">#ID</th>
                        <th>{{ __('messages.resellers.status') }}</th>
                        <th class="text-center">{{ __('messages.resellers.total_items') }}</th>
                        <th class="text-center">{{ __('messages.resellers.total_amount') }}</th>
                        @if($resellerType === 'buyer')
                        <th>{{ __('messages.resellers.invoice_status') }}</th>
                        @endif
                        <th>{{ __('messages.resellers.created_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveries as $delivery)
                        @php
                            $totalItems = $delivery->products->sum('pivot.quantity');
                            $invoice = $delivery->invoice;
                        @endphp
                        <tr>
                            <td style="width: 1%; white-space: nowrap;" class="text-start">
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownDelivery{{ $delivery->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownDelivery{{ $delivery->id }}">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('reseller-stock-deliveries.edit', [$reseller->id, $delivery->id]) }}">
                                                <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                            </a>
                                        </li>
                                        @if($resellerType === 'buyer' && $invoice)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('invoices.download', $invoice->id) }}">
                                                <i class="bi bi-download"></i> {{ __('messages.btn.invoice') }}
                                            </a>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                            <td class="text-center">{{ $delivery->id }}</td>
                            <td>{{ ucfirst($delivery->status) }}</td>
                            <td class="text-center">{{ $totalItems }}</td>
                            <td class="text-center">{{ $invoice ? number_format($invoice->total_amount, 2, ',', ' ') . ' $' : '-' }}</td>
                            @if($resellerType === 'buyer')
                            <td>
                                @if($invoice)
                                    @php
                                        $badgeClass = match($invoice->status) {
                                            'unpaid' => 'danger',
                                            'paid' => 'success',
                                            'cancelled' => 'secondary',
                                            'partially_paid' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">-</span>
                                @endif
                            </td>
                            @endif
                            <td>{{ $delivery->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($deliveries instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $deliveries->links() }}
            @endif
        </div>
    </div>
</div>

<style>
/* S'assurer que le modal-footer est visible */
.modal-dialog-scrollable .modal-content {
    max-height: calc(100vh - 3.5rem);
}

.modal-dialog-scrollable .modal-body {
    overflow-y: auto;
}

.modal-footer {
    display: flex !important;
    flex-shrink: 0;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    padding: 0.75rem;
    border-top: 1px solid #dee2e6;
}
</style>

<script>
// Gérer l'affichage de l'onglet selon le paramètre tab
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');

    if (tab) {
        // Désactiver tous les onglets
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });

        // Activer l'onglet demandé
        const targetTab = document.getElementById(tab + '-tab');
        const targetPane = document.getElementById(tab);

        if (targetTab && targetPane) {
            targetTab.classList.add('active');
            targetPane.classList.add('show', 'active');
        }
    }
});
</script>
@endsection
