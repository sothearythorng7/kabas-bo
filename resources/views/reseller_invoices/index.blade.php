@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Vue d'ensemble des factures revendeurs</h1>
    <div class="alert alert-warning">
        Montant total des factures en attente de paiement : <strong>${{ number_format($totalPending, 2) }}</strong>
    </div>

    <ul class="nav nav-tabs mb-3" id="invoiceStatusTab" role="tablist">
        @foreach($statuses as $status)
            <li class="nav-item" role="presentation">
                <a class="nav-link @if($loop->first) active @endif" 
                   id="tab-{{ $status }}" 
                   data-bs-toggle="tab" 
                   href="#content-{{ $status }}" 
                   role="tab">
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
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
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Commande liée</th>
                                <th>Montant total</th>
                                <th>Statut</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoicesByStatus[$status] as $invoice)
                                <tr>
                                    <td>{{ $invoice->reseller?->name ?? $invoice->store?->name ?? '—' }}</td>
                                    <td>{{ $invoice->reseller?->type ?? ($invoice->store ? 'store' : '—') }}</td>
                                    <td>
                                        @if($invoice->reseller_stock_delivery_id)
                                            commande
                                        @elseif($invoice->sales_report_id)
                                            rapport de vente
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
                                            {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('reseller-invoices.show', $invoice) }}" class="btn btn-sm btn-info">Détails</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">Aucune facture pour ce statut.</td>
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
                                        <h5 class="card-title mb-1">{{ $invoice->reseller?->name ?? $invoice->store?->name ?? '—' }}</h5>
                                        <p class="mb-1"><strong>Type:</strong> {{ $invoice->reseller?->type ?? ($invoice->store ? 'store' : '—') }}</p>
                                        <p class="mb-1">
                                            <strong>Commande liée:</strong>
                                            @if($invoice->reseller_stock_delivery_id)
                                                commande
                                            @elseif($invoice->sales_report_id)
                                                rapport de vente
                                            @else
                                                ---
                                            @endif
                                        </p>
                                        <p class="mb-1"><strong>Montant total:</strong> ${{ number_format($invoice->total_amount, 2) }}</p>
                                        <p class="mb-1">
                                            <strong>Statut:</strong>
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
                                                {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                            </span>
                                        </p>
                                        <p class="mb-2"><strong>Créée le:</strong> {{ $invoice->created_at->format('d/m/Y H:i') }}</p>
                                        <a href="{{ route('reseller-invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                            Détails
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-light text-center">
                                    Aucune facture pour ce statut.
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
