@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Vue d'ensemble des factures</h1>
        <a href="{{ route('warehouse-invoices.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.warehouse_invoices.new_invoice') }}
    </a>

    <ul class="nav nav-tabs mb-3" id="invoiceStatusTab" role="tablist">
        @foreach($statuses as $status)
            <li class="nav-item" role="presentation">
                <a class="nav-link @if($loop->first) active @endif" 
                   id="tab-{{ $status->value }}" 
                   data-bs-toggle="tab" 
                   href="#content-{{ $status->value }}" 
                   role="tab">
                    {{ $status->label() }} <span class="badge bg-secondary">{{ $invoicesByStatus[$status->value]->total() }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($statuses as $status)
            <div class="tab-pane fade @if($loop->first) show active @endif" id="content-{{ $status->value }}" role="tabpanel">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('messages.warehouse_invoices.creditor') }}</th>
                            <th>{{ __('messages.warehouse_invoices.type') }}</th>
                            <th>{{ __('messages.warehouse_invoices.amount_usd') }}</th>
                            <th>{{ __('messages.warehouse_invoices.amount_riel') }}</th>
                            <th>{{ __('messages.warehouse_invoices.date') }}</th>
                            <th>{{ __('messages.warehouse_invoices.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoicesByStatus[$status->value] as $invoice)
                            <tr>
                                <td>{{ $invoice->creditor_name }}</td>
                                <td>{{ $invoice->type->label() }}</td>
                                <td>${{ number_format($invoice->amount_usd, 2) }}</td>
                                <td>{{ number_format($invoice->amount_riel, 0) }} ៛</td>
                                <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('warehouse-invoices.edit', $invoice) }}" class="btn btn-sm btn-warning">Éditer</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">Aucune facture pour ce statut.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $invoicesByStatus[$status->value]->links() }}
            </div>
        @endforeach
    </div>
</div>
@endsection
