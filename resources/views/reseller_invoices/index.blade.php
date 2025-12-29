@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.reseller_invoice.overview_title') }}</h1>
    <div class="alert alert-warning">
        {{ __('messages.reseller_invoice.pending_total') }} : <strong>${{ number_format($totalPending, 2) }}</strong>
    </div>

    <ul class="nav nav-tabs mb-3" id="invoiceStatusTab" role="tablist">
        @foreach($statuses as $status)
            <li class="nav-item" role="presentation">
                <a class="nav-link @if($loop->first) active @endif"
                   id="tab-{{ $status }}"
                   data-bs-toggle="tab"
                   href="#content-{{ $status }}"
                   role="tab">
                    {{ __('messages.reseller_invoice.status.' . $status) }}
                    <span class="badge bg-secondary">{{ $invoicesByStatus[$status]->total() }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($statuses as $status)
            <div class="tab-pane fade @if($loop->first) show active @endif" id="content-{{ $status }}" role="tabpanel">

                <!-- Desktop -->
                <div class="d-none d-md-block">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th></th> <!-- dropdown column -->
                                <th>{{ __('messages.common.name') }}</th>
                                <th>{{ __('messages.resellers.type') }}</th>
                                <th>{{ __('messages.reseller_invoice.linked_order') }}</th>
                                <th>{{ __('messages.reseller_invoice.total_amount') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.reseller_invoice.created_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoicesByStatus[$status] as $invoice)
                                <tr>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="actionsDropdown{{ $invoice->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="actionsDropdown{{ $invoice->id }}">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('reseller-invoices.show', $invoice) }}">
                                                        <i class="bi bi-info-circle"></i> {{ __('messages.btn.details') }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>{{ $invoice->reseller?->name ?? $invoice->store?->name ?? '—' }}</td>
                                    <td>{{ $invoice->reseller?->type ?? ($invoice->store ? 'store' : '—') }}</td>
                                    <td>
                                        @if($invoice->reseller_stock_delivery_id)
                                            {{ __('messages.reseller_invoice.order') }}
                                        @elseif($invoice->sales_report_id)
                                            {{ __('messages.reseller_invoice.sales_report') }}
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td>${{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match($invoice->status) {
                                                'pending' => 'warning',
                                                'paid' => 'success',
                                                'cancelled' => 'danger',
                                                'overdue' => 'dark',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $badgeClass }}">
                                            {{ __('messages.reseller_invoice.status.' . $invoice->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">{{ __('messages.reseller_invoice.no_invoice_for_status') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile -->
                <div class="d-md-none">
                    <div class="row">
                        @forelse($invoicesByStatus[$status] as $invoice)
                            <div class="col-12 mb-3">
                                <div class="card shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-end mb-2">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="actionsDropdownMobile{{ $invoice->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="actionsDropdownMobile{{ $invoice->id }}">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('reseller-invoices.show', $invoice) }}">
                                                            <i class="bi bi-info-circle"></i> {{ __('messages.btn.details') }}
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <h5 class="card-title mb-1">{{ $invoice->reseller?->name ?? $invoice->store?->name ?? '—' }}</h5>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.type') }}:</strong> {{ $invoice->reseller?->type ?? ($invoice->store ? 'store' : '—') }}</p>
                                        <p class="mb-1">
                                            <strong>{{ __('messages.reseller_invoice.linked_order') }}:</strong>
                                            @if($invoice->reseller_stock_delivery_id)
                                                {{ __('messages.reseller_invoice.order') }}
                                            @elseif($invoice->sales_report_id)
                                                {{ __('messages.reseller_invoice.sales_report') }}
                                            @else
                                                ---
                                            @endif
                                        </p>
                                        <p class="mb-1"><strong>{{ __('messages.reseller_invoice.total_amount') }}:</strong> ${{ number_format($invoice->total_amount, 2) }}</p>
                                        <p class="mb-1">
                                            <strong>{{ __('messages.common.status') }}:</strong>
                                            @php
                                                $badgeClass = match($invoice->status) {
                                                    'pending' => 'warning',
                                                    'paid' => 'success',
                                                    'cancelled' => 'danger',
                                                    'overdue' => 'dark',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badgeClass }}">
                                                {{ __('messages.reseller_invoice.status.' . $invoice->status) }}
                                            </span>
                                        </p>
                                        <p class="mb-2"><strong>{{ __('messages.reseller_invoice.created_at') }}:</strong> {{ $invoice->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-light text-center">
                                    {{ __('messages.reseller_invoice.no_invoice_for_status') }}
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{ $invoicesByStatus[$status]->links() }}
            </div>
        @endforeach
    </div>
</div>
@endsection
