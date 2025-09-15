@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ isset($account) ? '@lang("Modifier")' : '@t("Créer")' }} @t("un compte") - {{ $site->name }}</h1>

    <form action="{{ isset($account) ? route('stores.accounts.update', [$site, $account]) : route('stores.accounts.store', $site) }}" method="POST">
        @csrf
        @if(isset($account))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label for="name" class="form-label">@t("Nom")</label>
            <input type="text" class="form-control" name="name" value="{{ $account->name ?? old('name') }}" required>
        </div>
        <div class="mb-3">
            <label for="type" class="form-label">@t("type")</label>
            <select class="form-select" name="type" required>
                <option value="cash" {{ (isset($account) && $account->type=='cash') ? 'selected' : '' }}>@t("Caisse")</option>
                <option value="bank" {{ (isset($account) && $account->type=='bank') ? 'selected' : '' }}>@t("Banque")</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="balance" class="form-label">@t("Solde initial")</label>
            <input type="number" step="0.01" class="form-control" name="balance" value="{{ $account->balance ?? old('balance') }}" required>
        </div>

        <button type="submit" class="btn btn-primary">{{ isset($account) ? "@t("'Mettre à jour'")" : "@t("'Créer'")" }}</button>
        <a href="{{ route('stores.accounts.index', $site) }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
