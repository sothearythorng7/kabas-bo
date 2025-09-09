@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.resellers.title_edit') }}: {{ $reseller->name }}</h1>

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="resellerTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab" aria-controls="contacts" aria-selected="true">
                {{ __('messages.resellers.contacts') }}
            </button>
        </li>

        @if($reseller->type === 'consignment')
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                {{ __('messages.product.products') }}
                <span class="badge bg-{{ ($products->total() ?? 0) > 0 ? 'success' : 'danger' }}">{{ $products->total() ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab" aria-controls="reports" aria-selected="false">
                {{ __('messages.resellers.sale_reports') }}
                <span class="badge bg-{{ ($salesReports->total() ?? 0) > 0 ? 'primary' : 'secondary' }}">{{ $salesReports->total() ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="anomalies-tab" data-bs-toggle="tab" data-bs-target="#anomalies" type="button" role="tab" aria-controls="anomalies" aria-selected="false">
                {{ __('messages.resellers.stock_anomalies') }}
                <span class="badge bg-{{ ($anomalies->total() ?? 0) > 0 ? 'danger' : 'secondary' }}">{{ $anomalies->total() ?? 0 }}</span>
            </button>
        </li>
        @endif

        <li class="nav-item" role="presentation">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                {{ __('messages.resellers.deliveries') }}
                <span class="badge bg-{{ ($deliveries->total() ?? 0) > 0 ? 'primary' : 'secondary' }}">{{ $deliveries->total() ?? 0 }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="resellerTabsContent">
        {{-- Onglet Contacts --}}
        <div class="tab-pane fade active" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
            {{-- Desktop --}}
            <div class="d-none d-md-block">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('messages.resellers.first_name') }}</th>
                            <th>{{ __('messages.resellers.last_name') }}</th>
                            <th>{{ __('messages.resellers.email') }}</th>
                            <th>{{ __('messages.resellers.phone') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reseller->contacts as $contact)
                            <tr>
                                <td>{{ $contact->first_name }}</td>
                                <td>{{ $contact->last_name }}</td>
                                <td>{{ $contact->email }}</td>
                                <td>{{ $contact->phone }}</td>
                                <td class="text-end">
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editContactModal{{ $contact->id }}">
                                        <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                    </button>
                                    <form action="{{ route('reseller-contacts.destroy', [$reseller, $contact]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.resellers.confirm_delete_contact') }}')">
                                            <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                        </button>
                                    </form>
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
                                    <h5 class="card-title mb-1">{{ $contact->first_name }} {{ $contact->last_name }}</h5>
                                    <p class="card-text mb-1"><strong>{{ __('messages.resellers.email') }}:</strong> {{ $contact->email }}</p>
                                    <p class="card-text mb-2"><strong>{{ __('messages.resellers.phone') }}:</strong> {{ $contact->phone }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if($reseller->type === 'consignment')
            {{-- Onglet Produits --}}
            <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="alert alert-info mb-0">
                        <strong>{{ __('messages.resellers.stock_value_total') }} :</strong> 
                        {{ number_format($reseller->getStockValue(), 2, ',', ' ') }} €
                    </div>
                </div>

                {{-- Desktop --}}
                <div class="d-none d-md-block">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>EAN</th>
                                <th>{{ __('messages.product.name') }}</th>
                                <th>{{ __('messages.product.brand') }}</th>
                                <th>{{ __('messages.product.price') }}</th>
                                <th>{{ __('messages.resellers.stock') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $p)
                                <tr>
                                    <td>{{ $p->ean }}</td>
                                    <td>{{ $p->name[app()->getLocale()] ?? reset($p->name) }}</td>
                                    <td>{{ $p->brand?->name ?? '-' }}</td>
                                    <td>{{ number_format($p->price, 2) }}</td>
                                    <td>{{ $stock[$p->id] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $products->links() }}
                </div>

                {{-- Mobile --}}
                <div class="d-md-none">
                    <div class="row">
                        @foreach($products as $p)
                            <div class="col-12 mb-3">
                                <div class="card shadow-sm">
                                    <div class="card-body p-3">
                                        <h5 class="card-title mb-1">{{ $p->name[app()->getLocale()] ?? reset($p->name) }}</h5>
                                        <p class="mb-1"><strong>EAN:</strong> {{ $p->ean }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.product.brand') }}:</strong> {{ $p->brand?->name ?? '-' }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.product.price') }}:</strong> {{ number_format($p->price, 2) }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.product.stock') }}:</strong> {{ $stock[$p->id] ?? 0 }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        {{ $products->links() }}
                    </div>
                </div>
            </div>

            {{-- Onglet Sales Reports --}}
            <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">{{ __('messages.resellers.sales_reports_title') }}</h3>
                    <a href="{{ route('resellers.reports.create', $reseller) }}" class="btn btn-success">
                        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.resellers.add_report') }}
                    </a>
                </div>

                {{-- Desktop --}}
                <div class="d-none d-md-block">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('messages.resellers.report_id') }}</th>
                                <th>{{ __('messages.resellers.created_at') }}</th>
                                <th>{{ __('messages.resellers.total_items') }}</th>
                                <th>{{ __('messages.resellers.total_value') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesReports as $report)
                                <tr>
                                    <td>#{{ $report->id }}</td>
                                    <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $report->items->count() }}</td>
                                    <td>{{ number_format($report->items->sum(fn($i) => $i->quantity_sold * $i->unit_price), 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('resellers.reports.show', [$reseller, $report]) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $salesReports->links() }}
                </div>

                {{-- Mobile --}}
                <div class="d-md-none">
                    <div class="row">
                        @foreach($salesReports as $report)
                            <div class="col-12 mb-3">
                                <div class="card shadow-sm">
                                    <div class="card-body p-3">
                                        <h5 class="card-title mb-1">#{{ $report->id }}</h5>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.created_at') }}:</strong> {{ $report->created_at->format('d/m/Y H:i') }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.total_items') }}:</strong> {{ $report->items->count() }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.total_value') }} (€):</strong> {{ number_format($report->items->sum(fn($i) => $i->quantity_sold * $i->unit_price), 2) }}</p>
                                        <a href="{{ route('resellers.reports.show', [$reseller, $report]) }}" class="btn btn-primary btn-sm mt-2">
                                            <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        {{ $salesReports->links() }}
                    </div>
                </div>
            </div>

            {{-- Onglet Anomalies --}}
            <div class="tab-pane fade" id="anomalies" role="tabpanel" aria-labelledby="anomalies-tab">
                {{-- Desktop --}}
                <div class="d-none d-md-block">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('messages.resellers.report_id') }}</th>
                                <th>{{ __('messages.resellers.product') }}</th>
                                <th>{{ __('messages.resellers.quantity') }}</th>
                                <th>{{ __('messages.resellers.description') }}</th>
                                <th>{{ __('messages.resellers.created_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($anomalies as $anomaly)
                                <tr>
                                    <td>#{{ $anomaly->report_id }}</td>
                                    <td>{{ is_array($anomaly->product->name) ? ($anomaly->product->name[app()->getLocale()] ?? reset($anomaly->product->name)) : $anomaly->product->name }}</td>
                                    <td>{{ $anomaly->quantity }}</td>
                                    <td>{{ $anomaly->description }}</td>
                                    <td>{{ $anomaly->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $anomalies->links() }}
                </div>

                {{-- Mobile --}}
                <div class="d-md-none">
                    <div class="row">
                        @foreach($anomalies as $anomaly)
                            <div class="col-12 mb-3">
                                <div class="card shadow-sm">
                                    <div class="card-body p-3">
                                        <h5 class="card-title mb-1">{{ __('messages.resellers.report_id') }} #{{ $anomaly->report_id }}</h5>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.product') }}:</strong> {{ is_array($anomaly->product->name) ? ($anomaly->product->name[app()->getLocale()] ?? reset($anomaly->product->name)) : $anomaly->product->name }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.quantity') }}:</strong> {{ $anomaly->quantity }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.description') }}:</strong> {{ $anomaly->description }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.created_at') }}:</strong> {{ $anomaly->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        {{ $anomalies->links() }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Onglet Livraisons --}}
        <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">{{ __('messages.resellers.deliveries') }}</h3>
                <a href="{{ route('resellers.deliveries.create', $reseller) }}" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.btn.add') }}
                </a>
            </div>

            {{-- Desktop --}}
            <div class="d-none d-md-block">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('messages.resellers.report_id') }}</th>
                            <th>{{ __('messages.resellers.status') }}</th>
                            <th>{{ __('messages.resellers.shipping_cost') }}</th>
                            <th>{{ __('messages.resellers.created_at') }}</th>
                            <th>{{ __('messages.resellers.updated_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deliveries as $delivery)
                            <tr>
                                <td>#{{ $delivery->id }}</td>
                                <td>{{ $delivery->status }}</td>
                                <td>{{ number_format($delivery->shipping_cost, 2) }}</td>
                                <td>{{ $delivery->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $delivery->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('reseller-stock-deliveries.edit', [$reseller, $delivery]) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $deliveries->links() }}
            </div>

            {{-- Mobile --}}
            <div class="d-md-none">
                <div class="row">
                    @foreach($deliveries as $delivery)
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body p-3">
                                    <h5 class="card-title mb-1">#{{ $delivery->id }}</h5>
                                    <p class="mb-1"><strong>{{ __('messages.resellers.status') }}:</strong> {{ $delivery->status }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.resellers.shipping_cost') }}:</strong> {{ number_format($delivery->shipping_cost, 2) }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.resellers.created_at') }}:</strong> {{ $delivery->created_at->format('d/m/Y H:i') }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.resellers.updated_at') }}:</strong> {{ $delivery->updated_at->format('d/m/Y H:i') }}</p>
                                    <a href="{{ route('reseller-stock-deliveries.edit', [$reseller, $delivery]) }}" class="btn btn-primary btn-sm mt-2">
                                        <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    {{ $deliveries->links() }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
