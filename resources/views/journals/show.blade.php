@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Détails transaction #{{ $journal->id }} - {{ $site->name }}</h1>

    <ul class="list-group">
        <li class="list-group-item"><strong>Date:</strong> {{ $journal->date->format('d/m/Y') }}</li>
        <li class="list-group-item"><strong>Compte:</strong> {{ $journal->account->name }}</li>
        <li class="list-group-item"><strong>Type:</strong> {{ ucfirst($journal->type) }}</li>
        <li class="list-group-item"><strong>Montant:</strong> {{ number_format($journal->amount, 2, ',', ' ') }} €</li>
        <li class="list-group-item"><strong>Description:</strong> {{ $journal->description }}</li>
    </ul>

    <a href="{{ route('stores.journals.index', $site) }}" class="btn btn-secondary mt-3">Retour</a>
</div>
@endsection
