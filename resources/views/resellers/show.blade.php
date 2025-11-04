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
                                    <label for="name" class="form-label">@t("Nom")</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">@t("email")</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">@t("Téléphone")</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@t("Fermer")</button>
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
            <table class="table table-striped table-hover mt-3">
                <thead>
                    <tr>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.brand') }}</th>
                        <th class="text-center">{{ __('messages.resellers.stock') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                            <td>{{ $product->brand->name ?? '-' }}</td>
                            <td class="text-center">{{ $stock[$product->id] ?? 0 }}</td>
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
@endsection
