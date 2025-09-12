@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Ajouter une transaction - {{ $site->name }}</h1>

    <form action="{{ route('stores.journals.store', $site) }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Compte</label>
            <select name="account_id" class="form-select" required>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }} ({{ ucfirst($account->type) }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" required>
                <option value="in">Entrée</option>
                <option value="out">Sortie</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Montant</label>
            <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description">{{ old('description') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="{{ route('stores.journals.index', $site) }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
