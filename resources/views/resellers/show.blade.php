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

            {{-- Desktop --}}
            <div class="d-none d-md-block">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('messages.resellers.name') }}</th>
                            <th>{{ __('messages.resellers.email') }}</th>
                            <th>{{ __('messages.resellers.phone') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reseller->contacts as $contact)
                            <tr>
                                <td>{{ $contact->name }}</td>
                                <td>{{ $contact->email }}</td>
                                <td>{{ $contact->phone }}</td>
                                <td class="text-end">
                                    @if($resellerType !== 'shop')
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editContactModal{{ $contact->id }}">
                                        <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                    </button>
                                    <form action="{{ route('resellers.contacts.destroy', [$reseller->id ?? 0, $contact]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.resellers.confirm_delete_contact') }}')">
                                            <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile --}}
            <div class="d-md-none">
                <div class="row">
                    @foreach($reseller->contacts as $contact)
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body p-3">
                                    <h5 class="card-title mb-1">{{ $contact->name }}</h5>
                                    <p class="card-text mb-1"><strong>{{ __('messages.resellers.email') }}:</strong> {{ $contact->email }}</p>
                                    <p class="card-text mb-2"><strong>{{ __('messages.resellers.phone') }}:</strong> {{ $contact->phone }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

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
                                    <label for="name" class="form-label">Nom</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
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
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.brand') }}</th>
                        <th>{{ __('messages.resellers.stock') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                            <td>{{ $product->brand->name ?? '-' }}</td>
                            <td>{{ $stock[$product->id] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $products->links() }}
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

            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('messages.resellers.created_at') }}</th>
                        <th>{{ __('messages.resellers.total_items') }}</th>
                        <th>{{ __('messages.resellers.total_amount') }}</th>
                        <th>{{ __('messages.resellers.invoice_status') }}</th>
                        <th class="text-end">{{ __('messages.btn.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesReports as $report)
                        @php
                            $invoice = $report->invoice;
                        @endphp
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>{{ $report->created_at->format('d/m/Y') }}</td>
                            <td>{{ $report->items->sum('quantity_sold') }}</td>
                            <td>{{ $invoice ? number_format($invoice->total_amount, 2, ',', ' ') . ' $' : '-' }}</td>
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
                            <td class="text-end">
                                <a href="{{ route('resellers.reports.show', [$reseller->id, $report->id]) }}" class="btn btn-info btn-sm">
                                    <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                </a>
                                <a href="{{ route('resellers.reports.invoice', [$reseller->id, $report->id]) }}" class="btn btn-secondary btn-sm">
                                    <i class="bi bi-file-earmark-text-fill"></i> {{ __('messages.btn.invoice') }}
                                </a>
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
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('messages.resellers.created_at') }}</th>
                        <th>{{ __('messages.resellers.details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($anomalies as $anomaly)
                        <tr>
                            <td>{{ $anomaly->id }}</td>
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

            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>{{ __('messages.resellers.status') }}</th>
                        <th>{{ __('messages.resellers.total_items') }}</th>
                        <th>{{ __('messages.resellers.created_at') }}</th>
                        <th class="text-end">{{ __('messages.btn.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveries as $delivery)
                        @php
                            $totalItems = $delivery->products->sum('pivot.quantity');
                        @endphp
                        <tr>
                            <td>{{ $delivery->id }}</td>
                            <td>{{ $delivery->status }}</td>
                            <td>{{ $totalItems }}</td>
                            <td>{{ $delivery->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('reseller-stock-deliveries.edit', [$reseller->id, $delivery->id]) }}" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                </a>
                                <a href="{{ route('reseller-stock-deliveries.show', [$reseller->id, $delivery->id]) }}" class="btn btn-info btn-sm">
                                    <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                </a>
                            </td>
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
@endsection
