@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ isset($account) ? 'Modifier' : 'Créer' }} un compte - {{ $site->name }}</h1>

    <form action="{{ isset($account) ? route('stores.accounts.update', [$site, $account]) : route('stores.accounts.store', $site) }}" method="POST">
        @csrf
        @if(isset($account))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label for="name" class="form-label">Nom</label>
            <input type="text" class="form-control" name="name" value="{{ $account->name ?? old('name') }}" required>
        </div>
        <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select class="form-select" name="type" required>
                <option value="cash" {{ (isset($account) && $account->type=='cash') ? 'selected' : '' }}>Caisse</option>
                <option value="bank" {{ (isset($account) && $account->type=='bank') ? 'selected' : '' }}>Banque</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="balance" class="form-label">Solde initial</label>
            <input type="number" step="0.01" class="form-control" name="balance" value="{{ $account->balance ?? old('balance') }}" required>
        </div>

        <button type="submit" class="btn btn-primary">{{ isset($account) ? 'Mettre à jour' : 'Créer' }}</button>
        <a href="{{ route('stores.accounts.index', $site) }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
