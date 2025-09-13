@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4 crud_title">Transactions – {{ $store->name }}</h1>
    @include('financial.layouts.nav')

    <div class="d-flex justify-content-between mb-3">
        <a href="{{ route('financial.transactions.create', $store->id) }}" class="btn btn-primary">
            Nouvelle transaction
        </a>

        <!-- Bouton pour ouvrir le modal de filtres -->
        <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
            Filtrer
        </button>
    </div>

    <!-- Modal pour les filtres -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="GET" action="{{ route('financial.transactions.index', $store->id) }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">Filtres des transactions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- Filtre par date -->
                            <div class="col-md-6">
                                <label for="date_from" class="form-label">Date depuis</label>
                                <input type="date" id="date_from" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="date_to" class="form-label">Date jusqu'à</label>
                                <input type="date" id="date_to" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>

                            <!-- Filtre par comptes -->
                            <div class="col-md-6">
                                <label for="account_ids" class="form-label">Compte(s)</label>
                                <select id="account_ids" name="account_ids[]" class="form-select" multiple>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" @if(collect(request('account_ids'))->contains($account->id)) selected @endif>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Maintenez Ctrl (Cmd sur Mac) pour sélectionner plusieurs comptes</small>
                            </div>

                            <!-- Filtre par montant -->
                            <div class="col-md-3">
                                <label for="amount_min" class="form-label">Montant min</label>
                                <input type="number" step="0.01" id="amount_min" name="amount_min" class="form-control" value="{{ request('amount_min') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="amount_max" class="form-label">Montant max</label>
                                <input type="number" step="0.01" id="amount_max" name="amount_max" class="form-control" value="{{ request('amount_max') }}">
                            </div>

                            <!-- Filtre par méthodes de paiement -->
                            <div class="col-md-6">
                                <label for="payment_method_ids" class="form-label">Méthode(s) de paiement</label>
                                <select id="payment_method_ids" name="payment_method_ids[]" class="form-select" multiple>
                                    @foreach($methods as $method)
                                        <option value="{{ $method->id }}" @if(collect(request('payment_method_ids'))->contains($method->id)) selected @endif>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Maintenez Ctrl (Cmd sur Mac) pour sélectionner plusieurs méthodes</small>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('financial.transactions.index', $store->id) }}" class="btn btn-outline-secondary">Réinitialiser</a>
                        <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tableau des transactions -->
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
