@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.resellers.title_edit') }}: {{ $reseller->name }}</h1>

    @php
        $resellerType = $reseller->type ?? 'buyer';
    @endphp

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="resellerTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab" aria-controls="contacts" aria-selected="true">
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
                {{ __('messages.resellers.stock_anomalies') }}
                <span class="badge bg-{{ ($anomalies instanceof \Illuminate\Pagination\LengthAwarePaginator ? $anomalies->total() : $anomalies->count()) > 0 ? 'danger' : 'secondary' }}">
                    {{ $anomalies instanceof \Illuminate\Pagination\LengthAwarePaginator ? $anomalies->total() : $anomalies->count() }}
                </span>
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

        {{-- Onglet Contacts --}}
        <div class="tab-pane fade show active" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
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
                        <th class="text-center">{{ __('messages.btn.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        @php
                            $currentStock = $stock[$product->id] ?? 0;
                            $alertThreshold = $alertStocks[$product->id] ?? 0;
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
                            <td class="text-center">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editStockModal{{ $product->id }}">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                            </td>
                        </tr>

                        {{-- Modal pour éditer le stock --}}
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
                        <th>{{ __('messages.resellers.created_at') }}</th>
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
                                    </ul>
                                </div>
                            </td>
                            <td class="text-center">{{ $report->id }}</td>
                            <td>{{ $report->created_at->format('d/m/Y') }}</td>
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

        {{-- Onglet Anomalies --}}
        <div class="tab-pane fade" id="anomalies" role="tabpanel" aria-labelledby="anomalies-tab">
            <table class="table table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th class="text-center">ID</th>
                        <th>{{ __('messages.resellers.created_at') }}</th>
                        <th>{{ __('messages.resellers.details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($anomalies as $anomaly)
                        <tr>
                            <td class="text-center">{{ $anomaly->id }}</td>
                            <td>{{ $anomaly->created_at->format('d/m/Y') }}</td>
                            <td>{{ $anomaly->details ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($anomalies instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $anomalies->links() }}
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
