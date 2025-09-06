@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.warehouse_invoices.invoices') }}</h1>
    <a href="{{ route('warehouse-invoices.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.warehouse_invoices.new_invoice') }}
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('messages.warehouse_invoices.creditor') }}</th>
                <th>{{ __('messages.warehouse_invoices.type') }}</th>
                <th>{{ __('messages.warehouse_invoices.amount_usd') }}</th>
                <th>{{ __('messages.warehouse_invoices.amount_riel') }}</th>
                <th>{{ __('messages.warehouse_invoices.status') }}</th>
                <th>{{ __('messages.warehouse_invoices.date') }}</th>
                <th>{{ __('messages.warehouse_invoices.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->creditor_name }}</td>
                    <td>{{ $invoice->type->label() }}</td>
                    <td>${{ number_format($invoice->amount_usd, 2) }}</td>
                    <td>{{ number_format($invoice->amount_riel, 0) }} ៛</td>
                    <td>
                        <span class="badge bg-primary">{{ $invoice->status->label() }}</span>
                    </td>
                    <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('warehouse-invoices.edit', $invoice) }}" class="btn btn-sm btn-warning">
                            {{ __('messages.warehouse_invoices.edit') }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">{{ __('messages.warehouse_invoices.no_invoices') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $invoices->links() }}
</div>
@endsection
