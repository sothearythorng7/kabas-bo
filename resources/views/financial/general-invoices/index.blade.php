@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("General invoices") – {{ $store->name }}</h1>
    @include('financial.layouts.nav')

    <div class="d-flex justify-content-between mb-3 align-items-center">
        <div class="btn-toolbar" role="toolbar" aria-label="Barre d'actions">
            <div class="btn-group me-2" role="group" aria-label="Actions principales">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#invoiceModal">
                    <i class="bi bi-plus-lg"></i>@t("Add Invoice")
                </button>
            </div>
        </div>
    </div>

    <!-- Onglets par statut -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link @if(request('status') !== 'paid') active @endif"
               href="{{ route('financial.general-invoices.index', $store->id) }}">@t("to_pay")</a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if(request('status') === 'paid') active @endif"
               href="{{ route('financial.general-invoices.index', [$store->id, 'status' => 'paid']) }}">@t("paid")</a>
        </li>
    </ul>

    <!-- Tableau des factures -->
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th></th> <!-- Dropdown actions -->
                <th>@t("Libellé")</th>
                <th>@t("Compte")</th>
                <th>@t("Montant")</th>
                <th>@t("Due to")</th>
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
                                <li><a class="dropdown-item" href="{{ route('financial.general-invoices.show', [$store->id, $invoice->id]) }}">@t("See")</a></li>
                                <li><a class="dropdown-item" href="{{ route('financial.general-invoices.edit', [$store->id, $invoice->id]) }}">@t("edit")</a></li>
                                <li>
                                    <form method="POST" action="{{ route('financial.general-invoices.destroy', [$store->id, $invoice->id]) }}" class="m-0 p-0">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item" onclick="return confirm('@t("Confirmer la suppression ?")')">@t("Supprimer")</button>
                                    </form>
                                </li>
                            @elseif($invoice->type === 'supplier')
                                <li><a class="dropdown-item" href="{{ route('supplier-orders.show', [$invoice->supplier_id, $invoice->id]) }}">@t("See")</a></li>
                            @endif
                        </ul>
                    </div>
                </td>
                <td>
                    @if($invoice->type === 'general')
                        {{ $invoice->label }}
                    @elseif($invoice->type === 'supplier')
                        @t("Supplier order") #{{ $invoice->id }} – {{ $invoice->supplier->name }}
                    @endif
                </td>
                <td>
                    @if($invoice->type === 'general')
                        {{ $invoice->account?->name ?? '-' }}
                    @elseif($invoice->type === 'supplier')
                        @t("Fournisseur")
                    @endif
                </td>
                <td>
                    @if($invoice->type === 'general')
                        ${{ number_format($invoice->amount, 2) }}
                    @elseif($invoice->type === 'supplier')
                        ${{ number_format($invoice->products->sum(fn($p) => ($p->pivot->price_invoiced ?? $p->pivot->purchase_price) * ($p->pivot->quantity_received ?? 0)), 2) }}
                    @endif
                </td>
                <td>
                    @if($invoice->type === 'general')
                        {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}
                    @elseif($invoice->type === 'supplier')
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center">@t("No invoices to show")</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $invoices->links() }}

    <!-- Modal création/édition -->
    <div class="modal fade" id="invoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                @php
                    $isEdit = isset($editingInvoice);
                    $action = $isEdit 
                        ? route('financial.general-invoices.update', [$store->id, $editingInvoice->id]) 
                        : route('financial.general-invoices.store', $store->id);
                    $method = $isEdit ? 'PUT' : 'POST';
                    $invoice = $isEdit ? $editingInvoice : null;
                @endphp
                <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif
                    <div class="modal-header">
                        <h5 class="modal-title">@if($isEdit) @t("Edit invoice") @else @t("new Invoice") @endif</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>@t("Libellé")</label>
                            <input type="text" name="label" class="form-control" value="{{ old('label', $invoice->label ?? '') }}" required>
                        </div>
                        <div class="mb-3">
                            <label>@t("Note")</label>
                            <textarea name="note" class="form-control">{{ old('note', $invoice->note ?? '') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label>@t("Montant")</label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $invoice->amount ?? '') }}" required>
                        </div>
                        <div class="mb-3">
                            <label>@t("Date échéance")</label>
                            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($invoice)->due_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="mb-3">
                            <label>@t("Compte")</label>
                            <select name="account_id" class="form-select" required>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" {{ old('account_id', optional($invoice)->account_id) == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>@t("Attachment")</label>
                            <input type="file" name="attachment" class="form-control" {{ $isEdit ? '' : 'required' }}>
                            @if($isEdit && $invoice->attachment)
                                <a href="{{ Storage::url($invoice->attachment) }}" target="_blank">@t("Voir le fichier actuel")</a>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@t("btn.cancel")</button>
                        <button type="submit" class="btn btn-primary">@if($isEdit) @t("btn.update") @else @t("btn.create") @endif</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@if(isset($editingInvoice))
<script>
    // Ouvrir automatiquement la modal si on est en édition
    var invoiceModal = new bootstrap.Modal(document.getElementById('invoiceModal'));
    invoiceModal.show();
</script>
@endif

@endsection
