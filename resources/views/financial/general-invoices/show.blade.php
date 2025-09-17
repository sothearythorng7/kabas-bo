@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Détails de la facture</h1>
    @include('financial.layouts.nav')   

    <div class="card">
        <div class="card-body">
            <p><strong>@t('Libellé'):</strong> {{ $generalInvoice->label }}</p>
            <p><strong>@t('Note'):</strong> {{ $generalInvoice->note }}</p>
            <p><strong>@t('Montant'):</strong> {{ number_format($generalInvoice->amount,2) }}</p>
            <p><strong>@t('Date limite'):</strong> {{ $generalInvoice->due_date?->format('d/m/Y') ?? '-' }}</p>
            <p><strong>@t('Compte'):</strong> {{ $generalInvoice->account->name }}</p>
            <p><strong>@t('Statut'):</strong>
                @if($generalInvoice->status=='paid')
                    <span class="badge bg-success">@t('Payée')</span>
                @else
                    <span class="badge bg-warning">@t('À payer')</span>
                @endif
            </p>
            <p><strong>@t('Pièce jointe'):</strong> <a href="{{ Storage::url($generalInvoice->attachment) }}" target="_blank">@t('Télécharger')</a></p>
        </div>
    </div>
</div>
@endsection
