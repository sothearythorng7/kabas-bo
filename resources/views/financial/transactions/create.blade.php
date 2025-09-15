@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Nouvelle transaction") – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <form method="POST" action="{{ route('financial.transactions.store', $store->id) }}" enctype="multipart/form-data" class="mt-3">
        @csrf

        <div class="mb-3">
            <label class="form-label">@t("date")</label>
            <input type="date" name="transaction_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">@t("Libellé")</label>
            <input type="text" name="label" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">@t("description")</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">@t("Compte")</label>
            <select name="account_id" class="form-select" required>
                <option value="">-- @t("Choisir") --</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">@t("type")</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="direction" value="debit" required>
                <label class="form-check-label">@t("Débit")</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="direction" value="credit" required>
                <label class="form-check-label">@t("Crédit")</label>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">@t("Montant")</label>
            <input type="number" step="0.01" name="amount" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">@t("Devise")</label>
            <select name="currency" class="form-select">
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">@t("Méthode de paiement")</label>
            <select name="payment_method_id" class="form-select" required>
                @foreach($methods as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">@t("Pièces jointes")</label>
            <input type="file" name="attachments[]" multiple class="form-control">
        </div>

        <button type="submit" class="btn btn-success">@t("Enregistrer")</button>
        <a href="{{ route('financial.transactions.index', $store->id) }}" class="btn btn-secondary">@t("Annuler")</a>
    </form>
</div>
@endsection
    