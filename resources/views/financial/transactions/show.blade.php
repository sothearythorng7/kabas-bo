@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Détail transaction") – {{ $store->name }}</h1>
    @include('financial.layouts.nav')

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ $transaction->label }}</h5>
            <p class="card-text">{{ $transaction->description }}</p>

            <table class="table table-sm">
                <tr>
                    <th>@t("date")</th>
                    <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>@t("Compte")</th>
                    <td>{{ $transaction->account->code }} - {{ $transaction->account->name }}</td>
                </tr>
                <tr>
                    <th>@t("type")</th>
                    <td>{{ ucfirst($transaction->direction) }}</td>
                </tr>
                <tr>
                    <th>@t("Montant")</th>
                    <td class="{{ $transaction->direction === 'debit' ? 'text-danger' : 'text-success' }}">
                        {{ $transaction->direction === 'debit' ? '-' : '+' }} {{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                    </td>
                </tr>
                <tr>
                    <th>@t("Méthode de paiement")</th>
                    <td>{{ $transaction->paymentMethod->name }}</td>
                </tr>
                <tr>
                    <th>@t("Solde avant")</th>
                    <td>{{ number_format($transaction->balance_before, 2) }} {{ $transaction->currency }}</td>
                </tr>
                <tr>
                    <th>@t("Solde après")</th>
                    <td>{{ number_format($transaction->balance_after, 2) }} {{ $transaction->currency }}</td>
                </tr>
                <tr>
                    <th>@t("Utilisateur")</th>
                    <td>{{ $transaction->user?->name }}</td>
                </tr>
                <tr>
                    <th>@t("Statut")</th>
                    <td>{{ ucfirst($transaction->status) }}</td>
                </tr>
                @if($transaction->external_reference)
                <tr>
                    <th>@t("Lien vers la commande")</th>
                    <td><a href="{{ url($transaction->external_reference) }}" class="btn btn-success btn-sm">@t("Voir la commande")</a></td>
                </tr>
                @endif
            </table>

            @if($transaction->attachments->count())
                <h5 class="mt-4">@t("Pièces jointes")</h5>
                <ul class="list-group">
                    @foreach($transaction->attachments as $file)
                        <li class="list-group-item">
                            <a href="{{ $file->url }}" target="_blank"><i class="bi bi-card-image" style="font-size:5em;"></i></a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('financial.transactions.edit', [$store->id, $transaction->id]) }}" class="btn btn-warning">@t("Modifier")</a>
        <a href="{{ route('financial.transactions.index', $store->id) }}" class="btn btn-secondary">@t("Retour")</a>
    </div>
</div>
@endsection
