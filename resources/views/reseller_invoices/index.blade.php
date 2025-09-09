@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Vue d'ensemble des factures revendeurs</h1>
    
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
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Revendeur</th>
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
                                <td>{{ $invoice->reseller->name }}</td>
                                <td>#{{ $invoice->resellerStockDelivery->id ?? '-' }}</td>
                                <td>${{ number_format($invoice->total_amount, 2) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</td>
                                <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('reseller-invoices.edit', $invoice) }}" class="btn btn-sm btn-warning">Éditer</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">Aucune facture pour ce statut.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $invoicesByStatus[$status]->links() }}
            </div>
        @endforeach
    </div>
</div>
@endsection
