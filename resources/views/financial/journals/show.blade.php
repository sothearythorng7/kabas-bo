@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Détail journal – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <div class="card">
        <div class="card-body">
            <p><strong>Date :</strong> {{ $journal->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Transaction :</strong> {{ $journal->transaction?->label ?? '—' }}</p>
            <p><strong>Utilisateur :</strong> {{ $journal->user?->name }}</p>
            <p><strong>Action :</strong> {{ ucfirst($journal->action) }}</p>

            <h5>Avant modification :</h5>
            <pre>{{ json_encode($journal->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

            <h5>Après modification :</h5>
            <pre>{{ json_encode($journal->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('financial.journals.index', $store->id) }}" class="btn btn-secondary">Retour</a>
    </div>
</div>
@endsection
