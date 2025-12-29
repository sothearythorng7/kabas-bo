@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-truck"></i> {{ __('messages.btn.edit') }}: {{ $supplier->name }}</h1>

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="supplierTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                {{ __('messages.supplier.general_info') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab" aria-controls="contacts" aria-selected="false">
                {{ __('messages.supplier.contacts') }}
                <span class="badge bg-{{ $contactsCount > 0 ? 'primary' : 'secondary' }}">
                    {{ $contactsCount }}
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="raw-materials-tab" data-bs-toggle="tab" data-bs-target="#raw-materials" type="button" role="tab" aria-controls="raw-materials" aria-selected="false">
                {{ __('messages.factory.raw_materials') }}
                <span class="badge bg-{{ $rawMaterialsCount > 0 ? 'success' : 'danger' }}">
                    {{ $rawMaterialsCount }}
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
                    <strong>{{ __('messages.factory.unpaid_invoices') }} :</strong> {{ $unpaidOrdersCount }} {{ __('messages.factory.orders_count') }} -
                    {{ __('messages.factory.total_amount') }} : <strong>${{ number_format($totalUnpaidAmount, 2) }}</strong>
                </div>
            @endif

            <form action="{{ route('factory.suppliers.update', $supplier) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">{{ __('messages.common.name') }} *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">{{ __('messages.common.email') }}</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $supplier->email) }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">{{ __('messages.common.phone') }}</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">{{ __('messages.common.active') }}</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">{{ __('messages.common.address') }}</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $supplier->address) }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">{{ __('messages.common.notes') }}</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $supplier->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
                <a href="{{ route('factory.suppliers.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
            </form>
        </div>

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
                            <th>Telegram</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplier->contacts as $contact)
                            <tr>
                                <td>{{ $contact->first_name }}</td>
                                <td>{{ $contact->last_name }}</td>
                                <td>{{ $contact->email }}</td>
                                <td>{{ $contact->phone }}</td>
                                <td>{{ $contact->telegram }}</td>
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
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('messages.common.no_data') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Onglet Matières premières --}}
        <div class="tab-pane fade" id="raw-materials" role="tabpanel" aria-labelledby="raw-materials-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('factory.raw-materials.create') }}?supplier_id={{ $supplier->id }}" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                </a>
            </div>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('messages.common.name') }}</th>
                        <th>{{ __('messages.factory.sku') }}</th>
                        <th>{{ __('messages.factory.unit') }}</th>
                        <th class="text-center">{{ __('messages.factory.stock') }}</th>
                        <th class="text-center">{{ __('messages.factory.track_stock') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplier->rawMaterials as $material)
                        @php
                            $isLowStock = $material->track_stock && $material->isLowStock();
                        @endphp
                        <tr>
                            <td>
                                @if($isLowStock)
                                    <i class="bi bi-exclamation-triangle-fill text-warning" data-bs-toggle="tooltip" title="{{ __('messages.factory.low_stock') }}"></i>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('factory.raw-materials.edit', $material) }}">{{ $material->name }}</a>
                            </td>
                            <td>{{ $material->sku ?? '-' }}</td>
                            <td>{{ $material->unit }}</td>
                            <td class="text-center">
                                @if($material->track_stock)
                                    <span class="{{ $isLowStock ? 'text-danger fw-bold' : '' }}">
                                        {{ number_format($material->total_stock, 2) }} {{ $material->unit }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($material->track_stock)
                                    <i class="bi bi-check-circle text-success"></i>
                                @else
                                    <i class="bi bi-x-circle text-muted"></i>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle dropdown-noarrow" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('factory.raw-materials.edit', $material) }}">
                                                <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">{{ __('messages.common.no_data') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Onglet Commandes --}}
        <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                @if($supplier->rawMaterials->isNotEmpty())
                    <a href="{{ route('supplier-orders.create', $supplier) }}?type=raw_material" class="btn btn-success">
                        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                    </a>
                @else
                    <span></span>
                @endif
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
                            {{ __('messages.order.received') }} - {{ __('messages.factory.not_paid') }}
                        </option>
                        <option value="received_paid" {{ request('status')=='received_paid' ? 'selected' : '' }}>
                            {{ __('messages.order.received') }} - {{ __('messages.factory.paid') }}
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
                        <th>{{ __('messages.factory.items') }}</th>
                        <th>{{ __('messages.factory.expected_amount') }}</th>
                        <th>{{ __('messages.factory.invoiced_amount') }}</th>
                        <th>{{ __('messages.factory.paid') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $itemsCount = $order->rawMaterials->count();
                            $expectedAmount = $order->expectedAmount();
                            $invoicedAmount = $order->invoicedAmount();
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
                                                    <i class="bi bi-receipt"></i> {{ __('messages.factory.invoice_reception') }}
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
                            <td>{{ $order->created_at->format('d/m/Y') }}</td>
                            <td>{{ $itemsCount }}</td>
                            <td>${{ number_format($expectedAmount, 2) }}</td>
                            <td>
                                @if($invoicedAmount > 0)
                                    ${{ number_format($invoicedAmount, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($order->is_paid)
                                    <span class="badge bg-success">{{ __('messages.yes') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('messages.no') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">{{ __('messages.common.no_data') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $orders->appends(request()->query())->links() }}
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
                            <label class="form-label">{{ __('messages.supplier.first_name') }}</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('messages.supplier.last_name') }}</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('messages.supplier.email') }}</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('messages.supplier.phone') }}</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Telegram</label>
                            <input type="text" class="form-control" name="telegram">
                        </div>
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
                <h5 class="modal-title" id="editContactModalLabel{{ $contact->id }}">
                    {{ __('messages.supplier.edit_contact') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('contacts.update', [$supplier, $contact]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('messages.supplier.first_name') }}</label>
                            <input type="text" class="form-control" name="first_name" value="{{ $contact->first_name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('messages.supplier.last_name') }}</label>
                            <input type="text" class="form-control" name="last_name" value="{{ $contact->last_name }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('messages.supplier.email') }}</label>
                            <input type="email" class="form-control" name="email" value="{{ $contact->email }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('messages.supplier.phone') }}</label>
                            <input type="text" class="form-control" name="phone" value="{{ $contact->phone }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Telegram</label>
                            <input type="text" class="form-control" name="telegram" value="{{ $contact->telegram }}">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('messages.btn.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-success">
                        {{ __('messages.btn.save') }}
                    </button>
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
    // Affiche l'onglet correspondant au hash dans l'URL ou au paramètre tab
    var hash = window.location.hash;
    var urlParams = new URLSearchParams(window.location.search);
    var tabParam = urlParams.get('tab');

    if(hash) {
        var tabTriggerEl = document.querySelector('button[data-bs-target="' + hash + '"]');
        if(tabTriggerEl) {
            var tab = new bootstrap.Tab(tabTriggerEl);
            tab.show();
        }
    } else if(tabParam) {
        var tabTriggerEl = document.querySelector('button[data-bs-target="#' + tabParam + '"]');
        if(tabTriggerEl) {
            var tab = new bootstrap.Tab(tabTriggerEl);
            tab.show();
        }
    }

    // Met à jour le hash quand on change d'onglet
    var tabButtons = document.querySelectorAll('#supplierTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(function(btn){
        btn.addEventListener('shown.bs.tab', function(e){
            history.replaceState(null, null, e.target.getAttribute('data-bs-target'));
        });
    });

    // Ajoute le hash aux liens de pagination pour conserver l'onglet
    var paginationLinks = document.querySelectorAll('#orders .pagination a');
    paginationLinks.forEach(function(link){
        var url = new URL(link.href);
        url.hash = window.location.hash || '#orders';
        link.href = url.toString();
    });

    // Initialiser tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>
@endpush
