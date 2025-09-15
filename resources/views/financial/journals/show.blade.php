@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Détail journal") – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <div class="card">
        <div class="card-body">
            <p><strong>@t("date") :</strong> {{ $journal->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>@t("Transaction") :</strong> {{ $journal->transaction?->label ?? '—' }}</p>
            <p><strong>@t("Utilisateur") :</strong> {{ $journal->user?->name }}</p>
            <p><strong>@t("Action") :</strong> {{ ucfirst($journal->action) }}</p>

            <h5>@t("Avant modification") :</h5>
            <pre>{{ json_encode($journal->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

            <h5>@t("Après modification") :</h5>
            <pre>{{ json_encode($journal->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('financial.journals.index', $store->id) }}" class="btn btn-secondary">@t("Retour")</a>
    </div>
</div>
@endsection
