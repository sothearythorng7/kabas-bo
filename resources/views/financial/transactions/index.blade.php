@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4 crud_title">Transactions – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <a href="{{ route('financial.transactions.create', $store->id) }}" class="btn btn-primary mb-3">
        Nouvelle transaction
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Libellé</th>
                <th>Compte</th>
                <th>Montant</th>
                <th>Méthode</th>
                <th>Solde après</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($transactions as $t)
            <tr>
                <td>{{ $t->transaction_date->format('d/m/Y') }}</td>
                <td>{{ $t->label }}</td>
                <td>{{ $t->account->code }} - {{ $t->account->name }}</td>
                <td class="{{ $t->direction === 'debit' ? 'text-danger' : 'text-success' }}">
                    {{ $t->direction === 'debit' ? '-' : '+' }} {{ number_format($t->amount, 2) }} {{ $t->currency }}
                </td>
                <td>{{ $t->paymentMethod->name }}</td>
                <td>{{ number_format($t->balance_after, 2) }} {{ $t->currency }}</td>
                <td class="text-end">
                    <a href="{{ route('financial.transactions.show', [$store->id, $t->id]) }}" class="btn btn-sm btn-info">Voir</a>
                    <a href="{{ route('financial.transactions.edit', [$store->id, $t->id]) }}" class="btn btn-sm btn-warning">Modifier</a>
                    <form method="POST" action="{{ route('financial.transactions.destroy', [$store->id, $t->id]) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center">Aucune transaction</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $transactions->links() }}
</div>
@endsection
