@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t('Détails de la facture')</h1>
    @include('financial.layouts.nav')

    <div class="card">
        <div class="card-body">
            <p><strong>@t('Libellé'):</strong> {{ $generalInvoice->label }}</p>
            <p><strong>@t('Note'):</strong> {{ $generalInvoice->note }}</p>
            <p><strong>@t('Montant'):</strong> {{ number_format($generalInvoice->amount,2) }}</p>
            <p><strong>@t('Date limite'):</strong> {{ $generalInvoice->due_date?->format('d/m/Y') ?? '-' }}</p>
            <p><strong>@t('Compte'):</strong> {{ $generalInvoice->account->name }}</p>
            @if($generalInvoice->category)
                <p><strong>@t('Catégorie'):</strong>
                    <span class="badge" style="background-color: {{ $generalInvoice->category->color }}">
                        {{ $generalInvoice->category->name }}
                    </span>
                </p>
            @endif
            <p><strong>@t('Statut'):</strong>
                @if($generalInvoice->status=='paid')
                    <span class="badge bg-success">@t('Payée')</span>
                @else
                    <span class="badge bg-warning">@t('À payer')</span>
                @endif
            </p>
            @if($generalInvoice->payment_date)
                <p><strong>@t('Date de paiement'):</strong>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle"></i> {{ $generalInvoice->payment_date->format('d/m/Y') }}
                    </span>
                </p>
            @endif
            <p><strong>@t('Pièce jointe'):</strong> <a href="{{ route('financial.general-invoices.attachment', [$store->id, $generalInvoice->id]) }}" target="_blank">@t('Télécharger')</a></p>
        </div>
    </div>
</div>
@endsection
