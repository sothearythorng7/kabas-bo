@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier.title_edit') }}</h1>

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
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                {{ __('messages.product.products') }}
                <span class="badge bg-{{ ($products->total() ?? 0) > 0 ? 'success' : 'danger' }}">
                    {{ $products->total() ?? 0 }}
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                {{ __('messages.supplier.orders') }}
                <span class="badge bg-{{ ($orders->total() ?? 0) > 0 ? 'primary' : 'secondary' }}">
                    {{ $orders->total() ?? 0 }}
                </span>
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="supplierTabsContent">
        {{-- Onglet Infos générales --}}
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
                @csrf
                @method('PUT')
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

        {{-- Onglet Contacts --}}
        <div class="tab-pane fade" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">{{ __('messages.supplier.contacts') }}</h3>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addContactModal">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                </button>
            </div>

            {{-- Version desktop --}}
            <div class="d-none d-md-block">
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

            {{-- Version mobile --}}
            <div class="d-md-none">
                <div class="row">
                    @foreach($supplier->contacts as $contact)
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body p-3">
                                    <h5 class="card-title mb-1">{{ $contact->first_name }} {{ $contact->last_name }}</h5>
                                    <p class="card-text mb-1"><strong>{{ __('messages.supplier.email') }}:</strong> {{ $contact->email }}</p>
                                    <p class="card-text mb-2"><strong>{{ __('messages.supplier.phone') }}:</strong> {{ $contact->phone }}</p>
                                    <div class="d-flex gap-1 justify-content-end">
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Onglet Produits --}}
        <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
            {{-- Version desktop --}}
            <div class="d-none d-md-block">
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
                {{ $products->links() }}
            </div>

            {{-- Version mobile --}}
            <div class="d-md-none">
                <div class="row">
                    @foreach($products as $p)
                        @php
                            $lowStockStores = [];
                            foreach($p->stores as $store) {
                                if($store->pivot->stock_quantity <= $store->pivot->alert_stock_quantity) {
                                    $lowStockStores[] = $store->name . ', stock bas: ' . $store->pivot->stock_quantity;
                                }
                            }
                        @endphp
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body p-3">
                                    <h5 class="card-title mb-1">
                                        @if(count($lowStockStores))
                                            <i class="bi bi-exclamation-triangle-fill text-warning"
                                               data-bs-toggle="tooltip"
                                               title="{{ implode("\n", $lowStockStores) }}"></i>
                                        @endif
                                        {{ $p->name[app()->getLocale()] ?? reset($p->name) }}
                                    </h5>
                                    <p class="mb-1"><strong>EAN:</strong> {{ $p->ean }}</p>
                                    <p class="mb-1"><strong>Brand:</strong> {{ $p->brand?->name ?? '-' }}</p>
                                    <p class="mb-1"><strong>Price:</strong> {{ number_format($p->price, 2) }}</p>
                                    <p class="mb-1"><strong>Purchase Price:</strong></p>
                                    <form action="{{ route('suppliers.updatePurchasePrice', [$supplier, $p]) }}" method="POST" class="d-flex">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" step="0.01" name="purchase_price" value="{{ $p->pivot->purchase_price ?? 0 }}" class="form-control form-control-sm me-2">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-floppy-fill"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    {{ $products->links() }}
                </div>
            </div>
        </div>

        {{-- Onglet Commandes --}}
        <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">{{ __('messages.supplier.orders') }}</h3>
                <a href="{{ route('supplier-orders.create', $supplier) }}" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                </a>
            </div>

            {{-- Version desktop --}}
            <div class="d-none d-md-block">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('messages.supplier.status') }}</th>
                            <th>{{ __('messages.supplier.created_at') }}</th>
                            <th>{{ __('messages.supplier.updated_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>
                                    @if($order->status == 'pending')
                                        <span class="badge bg-warning">{{ __('messages.order.pending') }}</span>
                                    @elseif($order->status == 'waiting_reception')
                                        <span class="badge bg-info">{{ __('messages.order.waiting_reception') }}</span>
                                    @else
                                        <span class="badge bg-success">{{ __('messages.order.received') }}</span>
                                    @endif
                                </td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $order->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end d-flex gap-1 justify-content-end">
                                    <a href="{{ route('supplier-orders.show', [$supplier, $order]) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                    </a>
                                    @if($order->status === 'pending')
                                        <a href="{{ route('supplier-orders.edit', [$supplier, $order]) }}" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                        </a>
                                        <form action="{{ route('supplier-orders.validate', [$supplier, $order]) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('PUT')
                                            <button class="btn btn-success btn-sm">
                                                <i class="bi bi-check-circle-fill"></i> {{ __('messages.btn.validate') }}
                                            </button>
                                        </form>
                                    @elseif($order->status === 'waiting_reception')
                                        <a href="{{ route('supplier-orders.pdf', [$supplier, $order]) }}" class="btn btn-secondary btn-sm">
                                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                        </a>
                                        <a href="{{ route('supplier-orders.reception', [$supplier, $order]) }}" class="btn btn-success btn-sm">
                                            <i class="bi bi-box-seam"></i> {{ __('messages.order.reception') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $orders->links() }}
            </div>

            {{-- Version mobile --}}
            <div class="d-md-none">
                <div class="row">
                    @foreach($orders as $order)
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body p-3">
                                    <h5 class="card-title mb-1">#{{ $order->id }}</h5>
                                    <p class="card-text mb-1">
                                        <strong>{{ __('messages.supplier.status') }}:</strong>
                                        @if($order->status == 'pending')
                                            <span class="badge bg-warning">{{ __('messages.order.pending') }}</span>
                                        @elseif($order->status == 'waiting_reception')
                                            <span class="badge bg-info">{{ __('messages.order.waiting_reception') }}</span>
                                        @else
                                            <span class="badge bg-success">{{ __('messages.order.received') }}</span>
                                        @endif
                                    </p>
                                    <p class="card-text mb-1"><strong>{{ __('messages.supplier.created_at') }}:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                                    <div class="d-flex gap-1 justify-content-end mt-2">
                                        <a href="{{ route('supplier-orders.show', [$supplier, $order]) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        @if($order->status === 'pending')
                                            <a href="{{ route('supplier-orders.edit', [$supplier, $order]) }}" class="btn btn-warning btn-sm">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <form action="{{ route('supplier-orders.validate', [$supplier, $order]) }}" method="PUT" style="display:inline;">
                                                @csrf
                                                @method('PUT')
                                                <button class="btn btn-success btn-sm">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                </button>
                                            </form>
                                        @elseif($order->status === 'waiting_reception')
                                            <a href="{{ route('supplier-orders.pdf', [$supplier, $order]) }}" class="btn btn-secondary btn-sm">
                                                <i class="bi bi-file-earmark-pdf-fill"></i>
                                            </a>
                                            <a href="{{ route('supplier-orders.reception', [$supplier, $order]) }}" class="btn btn-success btn-sm">
                                                <i class="bi bi-box-seam"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                {{ $orders->links() }}
            </div>
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
