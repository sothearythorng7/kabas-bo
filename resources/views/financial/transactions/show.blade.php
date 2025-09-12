@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Détail transaction – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ $transaction->label }}</h5>
            <p class="card-text">{{ $transaction->description }}</p>

            <table class="table table-sm">
                <tr>
                    <th>Date</th>
                    <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Compte</th>
                    <td>{{ $transaction->account->code }} - {{ $transaction->account->name }}</td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td>{{ ucfirst($transaction->direction) }}</td>
                </tr>
                <tr>
                    <th>Montant</th>
                    <td class="{{ $transaction->direction === 'debit' ? 'text-danger' : 'text-success' }}">
                        {{ $transaction->direction === 'debit' ? '-' : '+' }} {{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                    </td>
                </tr>
                <tr>
                    <th>Méthode de paiement</th>
                    <td>{{ $transaction->paymentMethod->name }}</td>
                </tr>
                <tr>
                    <th>Solde avant</th>
                    <td>{{ number_format($transaction->balance_before, 2) }} {{ $transaction->currency }}</td>
                </tr>
                <tr>
                    <th>Solde après</th>
                    <td>{{ number_format($transaction->balance_after, 2) }} {{ $transaction->currency }}</td>
                </tr>
                <tr>
                    <th>Utilisateur</th>
                    <td>{{ $transaction->user?->name }}</td>
                </tr>
                <tr>
                    <th>Statut</th>
                    <td>{{ ucfirst($transaction->status) }}</td>
                </tr>
            </table>

            @if($transaction->attachments->count())
                <h5 class="mt-4">Pièces jointes</h5>
                <ul>
                    @foreach($transaction->attachments as $file)
                        <li>
                            <a href="{{ asset('storage/'.$file->path) }}" target="_blank">{{ $file->filename }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('financial.transactions.edit', [$store->id, $transaction->id]) }}" class="btn btn-warning">Modifier</a>
        <a href="{{ route('financial.transactions.index', $store->id) }}" class="btn btn-secondary">Retour</a>
    </div>
</div>
@endsection
