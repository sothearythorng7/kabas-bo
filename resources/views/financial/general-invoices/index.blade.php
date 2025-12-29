@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.general_invoices.title') }} – {{ $store->name }}</h1>
    @include('financial.layouts.nav')

    <div class="mb-3">
        <a href="{{ route('invoice-categories.index', ['store_id' => $store->id]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-tag"></i> {{ __('messages.general_invoices.manage_categories') }}
        </a>
    </div>

    <div class="d-flex justify-content-between mb-3 align-items-center">
        <div class="btn-toolbar" role="toolbar" aria-label="{{ __('messages.general_invoices.action_bar') }}">
            <div class="btn-group me-2" role="group" aria-label="{{ __('messages.general_invoices.main_actions') }}">
                <a href="{{ route('financial.general-invoices.create', $store->id) }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> {{ __('messages.general_invoices.add_invoice') }}
                </a>
            </div>
            <div class="btn-group" role="group" aria-label="{{ __('messages.general_invoices.export') }}">
                <a href="{{ route('financial.general-invoices.export', array_merge(['store' => $store->id], request()->all())) }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> {{ __('messages.general_invoices.export_excel') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Onglets par statut -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link @if(request('status') !== 'paid') active @endif"
               href="{{ route('financial.general-invoices.index', $store->id) }}">{{ __('messages.general_invoices.to_pay') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if(request('status') === 'paid') active @endif"
               href="{{ route('financial.general-invoices.index', [$store->id, 'status' => 'paid']) }}">{{ __('messages.general_invoices.paid') }}</a>
        </li>
    </ul>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> {{ __('messages.general_invoices.filters') }}</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('financial.general-invoices.index', $store->id) }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.general_invoices.category') }}</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- {{ __('messages.general_invoices.all_categories') }} --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.general_invoices.date_after') }}</label>
                        <input type="date" name="date_after" class="form-control" value="{{ request('date_after') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.general_invoices.date_before') }}</label>
                        <input type="date" name="date_before" class="form-control" value="{{ request('date_before') }}">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> {{ __('messages.general_invoices.filter') }}
                    </button>
                    <a href="{{ route('financial.general-invoices.index', [$store->id, 'status' => request('status')]) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> {{ __('messages.general_invoices.reset') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des factures -->
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th></th> <!-- Dropdown actions -->
                <th>{{ __('messages.general_invoices.label') }}</th>
                <th>{{ __('messages.general_invoices.category') }}</th>
                <th>{{ __('messages.general_invoices.account') }}</th>
                <th>{{ __('messages.general_invoices.amount') }}</th>
                <th>{{ __('messages.general_invoices.due_date') }}</th>
                <th>{{ __('messages.general_invoices.payment_date') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            @if($invoice->type === 'general')
                                <li><a class="dropdown-item" href="{{ route('financial.general-invoices.show', [$store->id, $invoice->id]) }}">{{ __('messages.general_invoices.view') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('financial.general-invoices.edit', [$store->id, $invoice->id]) }}">{{ __('messages.general_invoices.edit') }}</a></li>
                                @if($invoice->status !== 'paid')
                                    <li>
                                        <form method="POST" action="{{ route('financial.general-invoices.mark-as-paid', [$store->id, $invoice->id]) }}" class="m-0 p-0">
                                            @csrf
                                            <button class="dropdown-item text-success" onclick="return confirm('{{ __('messages.general_invoices.confirm_mark_paid') }}')">
                                                <i class="bi bi-check-circle"></i> {{ __('messages.general_invoices.mark_as_paid') }}
                                            </button>
                                        </form>
                                    </li>
                                @endif
                                <li>
                                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $invoice->id }}">
                                        <i class="bi bi-trash"></i> {{ __('messages.general_invoices.delete') }}
                                    </button>
                                </li>
                            @elseif($invoice->type === 'supplier')
                                <li><a class="dropdown-item" href="{{ route('supplier-orders.show', [$invoice->supplier_id, $invoice->id]) }}">{{ __('messages.general_invoices.view') }}</a></li>
                            @endif
                        </ul>
                    </div>
                </td>
                <td>
                    @if($invoice->type === 'general')
                        {{ $invoice->label }}
                    @elseif($invoice->type === 'supplier')
                        {{ __('messages.general_invoices.supplier_order') }} #{{ $invoice->id }} – {{ $invoice->supplier->name }}
                    @endif
                </td>
                <td>
                    @if($invoice->type === 'general' && $invoice->category)
                        <span class="badge" style="background-color: {{ $invoice->category->color }}">
                            {{ $invoice->category->name }}
                        </span>
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($invoice->type === 'general')
                        {{ $invoice->account?->name ?? '-' }}
                    @elseif($invoice->type === 'supplier')
                        {{ __('messages.general_invoices.supplier') }}
                    @endif
                </td>
                <td>
                    @if($invoice->type === 'general')
                        ${{ number_format($invoice->amount, 2) }}
                    @elseif($invoice->type === 'supplier')
                        ${{ number_format($invoice->products->sum(fn($p) => ($p->pivot->invoice_price ?? $p->pivot->purchase_price) * ($p->pivot->quantity_received ?? 0)), 2) }}
                    @endif
                </td>
                <td>
                    @if($invoice->type === 'general')
                        @if($invoice->due_date)
                            @php
                                $isOverdue = $invoice->due_date->isPast() && $invoice->status !== 'paid';
                            @endphp
                            <span class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                                @if($isOverdue)
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                @endif
                                {{ $invoice->due_date->format('d/m/Y') }}
                            </span>
                        @else
                            -
                        @endif
                    @elseif($invoice->type === 'supplier')
                        -
                    @endif
                </td>
                <td>
                    @if($invoice->type === 'general' && $invoice->payment_date)
                        <span class="badge bg-success">
                            <i class="bi bi-check-circle"></i> {{ $invoice->payment_date->format('d/m/Y') }}
                        </span>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center">{{ __('messages.general_invoices.no_invoices') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $invoices->links() }}

</div>

{{-- Modales de suppression pour chaque facture générale --}}
@foreach($invoices as $invoice)
    @if($invoice->type === 'general')
    <div class="modal fade" id="deleteModal-{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ __('messages.general_invoices.delete_confirmation_title') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>{{ $invoice->label }}</strong></p>
                    @if($invoice->status === 'paid')
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>{{ __('messages.general_invoices.delete_paid_warning_title') }}</strong>
                        </div>
                        <p>{{ __('messages.general_invoices.delete_paid_warning_message') }}</p>
                    @else
                        <p>{{ __('messages.general_invoices.delete_confirmation_message') }}</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('messages.btn.cancel') }}
                    </button>
                    <form action="{{ route('financial.general-invoices.destroy', [$store->id, $invoice->id]) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> {{ __('messages.general_invoices.confirm_delete_btn') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach

@endsection
