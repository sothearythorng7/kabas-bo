@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier.title_edit') }} - {{ $supplier->name }}</h1>

    @php
        $productsCount = $products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products->total() : $products->count();
        $ordersCount = $orders instanceof \Illuminate\Pagination\LengthAwarePaginator ? $orders->total() : $orders->count();
    @endphp

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="supplierTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                {{ __('messages.supplier.general_info') }}
            </button>
        </li>
        @if($supplier->type === 'consignment')
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sales-reports-tab" data-bs-toggle="tab" data-bs-target="#sales-reports" type="button" role="tab" aria-controls="sales-reports" aria-selected="false">
                @t("Sales reports")
            </button>
        </li>
        @endif
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab" aria-controls="contacts" aria-selected="false">
                {{ __('messages.supplier.contacts') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                {{ __('messages.product.products') }}
                <span class="badge bg-{{ $productsCount > 0 ? 'success' : 'danger' }}">
                    {{ $productsCount }}
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                {{ __('messages.supplier.orders') }}
                <span class="badge bg-{{ $ordersCount > 0 ? 'primary' : 'secondary' }}">
                    {{ $ordersCount }}
                </span>
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="supplierTabsContent">
        {{-- Onglet Infos générales --}}
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
            @if($unpaidOrdersCount > 0)
                <div class="alert alert-warning">
                    <strong>@t("Unpaid invoices") :</strong> {{ $unpaidOrdersCount }} @t("order(s)") - 
                    @t("total amount") de <strong>${{ number_format($totalUnpaidAmount, 2) }}</strong>
                </div>
            @endif
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">@t("Supplier type")</label>
                    @if($supplier->type === 'buyer')
                        <span class="badge bg-success">@t("Buyer")</span>
                    @elseif($supplier->type === 'consignment')
                        <span class="badge bg-info">@t("Consignment")</span>
                    @else
                        <span class="badge bg-secondary">-</span>
                    @endif
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('messages.supplier.name') }}</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">{{ __('messages.supplier.address') }}</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" required>{{ old('address', $supplier->address) }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
            </form>
        </div>

@if($supplier->type === 'consignment')
<div class="tab-pane fade" id="sales-reports" role="tabpanel" aria-labelledby="sales-reports-tab">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('sale-reports.create', $supplier) }}" class="btn btn-success">
            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
        </a>
    </div>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th>
                <th>@t("Store name")</th>
                <th>@t("Période")</th>
                <th>@t("Status")</th>
                <th>@t("Expected total")</th>
                <th>@t("Invoiced total")</th>
                <th>@t("Paid")</th>
            </tr>
        </thead>
        <tbody>
            @foreach($supplier->saleReports as $report)
            <tr>
                {{-- Boutons d’action --}}
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle dropdown-noarrow" type="button" id="actionsDropdown{{ $report->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="actionsDropdown{{ $report->id }}">
                            {{-- Voir --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('sale-reports.show', [$supplier, $report]) }}">
                                    <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                </a>
                            </li>

                            {{-- Réception facture --}}
                            @if($report->status === 'waiting_invoice')
                                <li>
                                    <a class="dropdown-item" href="{{ route('sale-reports.invoiceReception', [$supplier, $report]) }}">
                                        <i class="bi bi-receipt"></i> Réception facture
                                    </a>
                                </li>
                            @endif

                            {{-- Marquer comme payé --}}
                            @if($report->status === 'invoiced' && !$report->is_paid)
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#markAsPaidModal-{{ $report->id }}">
                                        <i class="bi bi-cash-stack"></i> Marquer comme payé
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>
                </td>

                {{-- Données principales --}}
                <td>{{ $report->store->name ?? '-' }}</td>
                <td>{{ $report->period_start->format('d/m/Y') }} - {{ $report->period_end->format('d/m/Y') }}</td>
                <td>
                    @if($report->status === 'waiting_invoice')
                        <span class="badge bg-secondary">En attente de facture</span>
                    @elseif($report->status === 'invoiced')
                        <span class="badge bg-info">Facture reçue</span>
                    @endif
                </td>
                <td>${{ number_format($report->total_amount_theoretical, 2) }}</td>
                <td>
                    @if($report->total_amount_invoiced)
                        ${{ number_format($report->total_amount_invoiced, 2) }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($report->is_paid)
                        <span class="badge bg-success">@t("Yes")</span>
                    @else
                        <span class="badge bg-danger">@t("No")</span>
                    @endif
                </td>
            </tr>

            {{-- Modal Mark as Paid --}}
            @if($report->status === 'invoiced' && !$report->is_paid)
            <div class="modal fade" id="markAsPaidModal-{{ $report->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('sale-reports.markAsPaid', [$supplier, $report]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">@t("Mark report as paid")</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">@t("Amount paid")</label>
                                    <input type="number" step="0.01" name="amount" class="form-control"
                                        value="{{ $report->total_amount_invoiced ?? $report->total_amount_theoretical }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">@t("Payment method")</label>
                                    <select name="payment_method_id" class="form-select" required>
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">@t("Payment reference")</label>
                                    <input type="text" name="payment_reference" class="form-control">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                <button type="submit" class="btn btn-success">@t("Confirm payment")</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            @endforeach
        </tbody>
    </table>
</div>
@endif


        {{-- Onglet Contacts --}}
        <div class="tab-pane fade" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addContactModal">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                </button>
            </div>

            <div class="d-block">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('messages.supplier.first_name') }}</th>
                            <th>{{ __('messages.supplier.last_name') }}</th>
                            <th>{{ __('messages.supplier.email') }}</th>
                            <th>{{ __('messages.supplier.phone') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($supplier->contacts as $contact)
                            <tr>
                                <td>{{ $contact->first_name }}</td>
                                <td>{{ $contact->last_name }}</td>
                                <td>{{ $contact->email }}</td>
                                <td>{{ $contact->phone }}</td>
                                <td class="text-end d-flex gap-1 justify-content-end">
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editContactModal{{ $contact->id }}">
                                        <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                    </button>
                                    <form action="{{ route('contacts.destroy', [$supplier, $contact]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.supplier.confirm_delete_contact') }}')">
                                            <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Onglet Produits --}}
        <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
            <div class="d-block">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th></th>
                            <th>EAN</th>
                            <th>{{ __('messages.product.name') }}</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Cost Price</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $p)
                            @php
                                $lowStockStores = [];
                                foreach($p->stores as $store) {
                                    if($store->pivot->stock_quantity <= $store->pivot->alert_stock_quantity) {
                                        $lowStockStores[] = $store->name . ', ' . __('messages.store.stocklow') . ': ' . $store->pivot->stock_quantity;
                                    }
                                }
                            @endphp
                            <tr>
                                <td>
                                    @if(count($lowStockStores))
                                        <i class="bi bi-exclamation-triangle-fill text-warning"
                                           data-bs-toggle="tooltip"
                                           title="{{ implode("\n", $lowStockStores) }}"></i>
                                    @endif
                                </td>
                                <td>{{ $p->ean }}</td>
                                <td>{{ $p->name[app()->getLocale()] ?? reset($p->name) }}</td>
                                <td>{{ $p->brand?->name ?? '-' }}</td>
                                <td>{{ number_format($p->price, 2) }}</td>
                                <td>
                                    <form action="{{ route('suppliers.updatePurchasePrice', [$supplier, $p]) }}" method="POST" class="d-flex">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" step="0.01" name="purchase_price" value="{{ $p->pivot->purchase_price ?? 0 }}" class="form-control form-control-sm me-2" style="max-width:100px;">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-floppy-fill"></i></button>
                                    </form>
                                </td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products->links() : '' }}
            </div>
        </div>

        {{-- Onglet Commandes --}}
        <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('supplier-orders.create', $supplier) }}" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                </a>
            </div>

            {{-- Filtre par statut --}}
            <div class="d-block mb-3">
                <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="hidden" name="tab" value="orders">
                    <label for="statusFilter" class="mb-0 me-1">{{ __('messages.supplier.filter_status') }}:</label>
                    <select name="status" id="statusFilter" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                        <option value="">{{ __('messages.supplier.all_statuses') }}</option>
                        <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>
                            {{ __('messages.order.pending') }}
                        </option>
                        <option value="waiting_reception" {{ request('status')=='waiting_reception' ? 'selected' : '' }}>
                            {{ __('messages.order.waiting_reception') }}
                        </option>
                        <option value="waiting_invoice" {{ request('status')=='waiting_invoice' ? 'selected' : '' }}>
                            {{ __('messages.order.waiting_invoice') }}
                        </option>
                        <option value="received_unpaid" {{ request('status')=='received_unpaid' ? 'selected' : '' }}>
                            {{ __('messages.order.received') }} - Non payé
                        </option>
                        <option value="received_paid" {{ request('status')=='received_paid' ? 'selected' : '' }}>
                            {{ __('messages.order.received') }} - Payé
                        </option>
                    </select>
                </form>
            </div>

            <table class="table table-striped text-center table-hover">
                <thead>
                    <tr>
                        <th></th>
                        <th>ID</th>
                        <th>{{ __('messages.supplier.status') }}</th>
                        <th>{{ __('messages.supplier.created_at') }}</th>
                        <th>Destination</th>
                        <th>Total commandé</th>
                        <th>Total reçu</th>
                        <th>Montant théorique</th>
                        <th>Payé</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        @php
                            $items = $order->products ?? collect();
                            $totalOrdered = ($order->status === 'received' || $order->status === 'waiting_invoice') 
                                ? $items->sum(fn($item) => $item->pivot->quantity_ordered ?? 0) 
                                : '-';
                            $totalReceived = ($order->status === 'received' || $order->status === 'waiting_invoice') 
                                ? $items->sum(fn($item) => $item->pivot->quantity_received ?? 0) 
                                : '-';
                            $totalAmount = ($order->status === 'received' || $order->status === 'waiting_invoice') 
                                ? $items->sum(fn($item) => ($item->pivot->purchase_price ?? 0) * ($item->pivot->quantity_received ?? 0)) 
                                : '-';
                        @endphp
                        <tr>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle dropdown-noarrow" type="button" id="actionsDropdown{{ $order->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="actionsDropdown{{ $order->id }}">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('supplier-orders.show', [$supplier, $order]) }}">
                                                <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                            </a>
                                        </li>
                                        @if($order->status === 'pending')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('supplier-orders.edit', [$supplier, $order]) }}">
                                                    <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('supplier-orders.validate', [$supplier, $order]) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button class="dropdown-item" type="submit">
                                                        <i class="bi bi-check-circle-fill"></i> {{ __('messages.btn.validate') }}
                                                    </button>
                                                </form>
                                            </li>
                                        @elseif($order->status === 'waiting_reception')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('supplier-orders.pdf', [$supplier, $order]) }}">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('supplier-orders.reception', [$supplier, $order]) }}">
                                                    <i class="bi bi-box-seam"></i> {{ __('messages.order.reception') }}
                                                </a>
                                            </li>
                                        @elseif($order->status === 'waiting_invoice')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('supplier-orders.pdf', [$supplier, $order]) }}">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('supplier-orders.invoiceReception', [$supplier, $order]) }}">
                                                    <i class="bi bi-receipt"></i> Réception de facture
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                            <td>#{{ $order->id }}</td>
                            <td>
                                @if($order->status == 'pending')
                                    <span class="badge bg-warning">{{ __('messages.order.pending') }}</span>
                                @elseif($order->status == 'waiting_reception')
                                    <span class="badge bg-info">{{ __('messages.order.waiting_reception') }}</span>
                                @elseif($order->status == 'waiting_invoice')
                                    <span class="badge bg-secondary">{{ __('messages.order.waiting_invoice') }}</span>
                                @else
                                    <span class="badge bg-success">{{ __('messages.order.received') }}</span>
                                @endif
                            </td>
                            <td>{{ $order->created_at->format('d/m/y') }}</td>
                            <td>{{ $order->destinationStore?->name ?? '-' }}</td>
                            <td>{{ $totalOrdered }}</td>
                            <td>{{ $totalReceived }}</td>
                            <td>
                                @if($totalAmount !== '-')
                                    ${{ number_format($totalAmount, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($order->is_paid)
                                    <span class="badge bg-success">Oui</span>
                                @else
                                    <span class="badge bg-danger">Non</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $orders instanceof \Illuminate\Pagination\LengthAwarePaginator ? $orders->appends(request()->query())->links() : '' }}
        </div>
    </div>
</div>

{{-- Modal Ajout Contact --}}
<div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addContactModalLabel">{{ __('messages.supplier.add_contact') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('contacts.store', $supplier) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">{{ __('messages.supplier.first_name') }}</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">{{ __('messages.supplier.last_name') }}</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('messages.supplier.email') }}</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">{{ __('messages.supplier.phone') }}</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('messages.btn.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modals Édition Contact --}}
@foreach($supplier->contacts as $contact)
<div class="modal fade" id="editContactModal{{ $contact->id }}" tabindex="-1" aria-labelledby="editContactModalLabel{{ $contact->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editContactModalLabel{{ $contact->id }}">{{ __('messages.supplier.edit_contact') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('contacts.update', [$supplier, $contact]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">{{ __('messages.supplier.first_name') }}</label>
                            <input type="text" class="form-control" name="first_name" value="{{ $contact->first_name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">{{ __('messages.supplier.last_name') }}</label>
                            <input type="text" class="form-control" name="last_name" value="{{ $contact->last_name }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('messages.supplier.email') }}</label>
                        <input type="email" class="form-control" name="email" value="{{ $contact->email }}">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">{{ __('messages.supplier.phone') }}</label>
                        <input type="text" class="form-control" name="phone" value="{{ $contact->phone }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('messages.btn.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Affiche l'onglet correspondant au hash dans l'URL
    var hash = window.location.hash;
    if(hash) {
        var tabTriggerEl = document.querySelector('button[data-bs-target="' + hash + '"]');
        if(tabTriggerEl) {
            var tab = new bootstrap.Tab(tabTriggerEl);
            console.log(tab);
            tab.show();
        }
    }
    // 2. Met à jour le hash quand on change d'onglet
    var tabButtons = document.querySelectorAll('#supplierTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(function(btn){
        btn.addEventListener('shown.bs.tab', function(e){
            history.replaceState(null, null, e.target.getAttribute('data-bs-target'));
        });
    });

    // 3. Ajoute le hash aux liens de pagination pour conserver l'onglet
    var paginationLinks = document.querySelectorAll('#orders .pagination a');
    paginationLinks.forEach(function(link){
        // enlève un hash existant s'il y en a
        var url = new URL(link.href);
        url.hash = window.location.hash || '#orders';
        link.href = url.toString();
    });

    // Initialiser tooltips pour alertes stock
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>
@endpush
