@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("General invoices") – {{ $store->name }}</h1>
    @include('financial.layouts.nav')

    <div class="d-flex justify-content-between mb-3 align-items-center">
        <div class="btn-toolbar" role="toolbar" aria-label="Barre d'actions">
            <div class="btn-group me-2" role="group" aria-label="Actions principales">
                <a href="{{ route('financial.general-invoices.create', $store->id) }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> @t("Add Invoice")
                </a>
            </div>
            <div class="btn-group" role="group" aria-label="Export">
                <a href="{{ route('financial.general-invoices.export', array_merge(['store' => $store->id], request()->all())) }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> @t("Exporter Excel")
                </a>
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

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> @t('Filtres')</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('financial.general-invoices.index', $store->id) }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">@t('Catégorie')</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- @t('Toutes les catégories') --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">@t('Date limite après')</label>
                        <input type="date" name="date_after" class="form-control" value="{{ request('date_after') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">@t('Date limite avant')</label>
                        <input type="date" name="date_before" class="form-control" value="{{ request('date_before') }}">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> @t('Filtrer')
                    </button>
                    <a href="{{ route('financial.general-invoices.index', [$store->id, 'status' => request('status')]) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> @t('Réinitialiser')
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
                <th>@t("Libellé")</th>
                <th>@t("Catégorie")</th>
                <th>@t("Compte")</th>
                <th>@t("Montant")</th>
                <th>@t("Due to")</th>
                <th>@t("Date de paiement")</th>
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
                                @if($invoice->status !== 'paid')
                                    <li>
                                        <form method="POST" action="{{ route('financial.general-invoices.mark-as-paid', [$store->id, $invoice->id]) }}" class="m-0 p-0">
                                            @csrf
                                            <button class="dropdown-item text-success" onclick="return confirm('@t("Marquer cette facture comme payée ?")')">
                                                <i class="bi bi-check-circle"></i> @t("Marquer comme payée")
                                            </button>
                                        </form>
                                    </li>
                                @endif
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
            <tr><td colspan="8" class="text-center">@t("No invoices to show")</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $invoices->links() }}

</div>

@endsection
