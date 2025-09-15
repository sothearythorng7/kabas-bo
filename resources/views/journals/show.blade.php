@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Détails transaction #{{ $journal->id }} - {{ $site->name }}</h1>

    <ul class="list-group">
        <li class="list-group-item"><strong>@t("date"):</strong> {{ $journal->date->format('d/m/Y') }}</li>
        <li class="list-group-item"><strong>@t("Compte"):</strong> {{ $journal->account->name }}</li>
        <li class="list-group-item"><strong>@t("type"):</strong> {{ ucfirst($journal->type) }}</li>
        <li class="list-group-item"><strong>@t("Montant"):</strong> {{ number_format($journal->amount, 2, ',', ' ') }} €</li>
        <li class="list-group-item"><strong>@t("description"):</strong> {{ $journal->description }}</li>
    </ul>

    <a href="{{ route('stores.journals.index', $site) }}" class="btn btn-secondary mt-3">@t("Retour")</a>
</div>
@endsection
