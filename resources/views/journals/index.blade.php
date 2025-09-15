@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Journal financier") - {{ $site->name }}</h1>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.dashboard.index', $site) }}">Informations générales</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('stores.journals.index', $site) }}">Journaux</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.payments.index', $site) }}">Paiements fournisseurs</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expenses.index', $site) }}">Dépenses</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expense-categories.index', $site) }}">Catégories</a>
        </li>
    </ul>
    <a href="{{ route('stores.journals.create', $site) }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> Ajouter une transaction
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Compte</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Description</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($journals as $journal)
            <tr>
                <td>{{ $journal->date->format('d/m/Y') }}</td>
                <td>{{ $journal->account->name }}</td>
                <td>{{ ucfirst($journal->type) }}</td>
                <td>{{ number_format($journal->amount, 2, ',', ' ') }} €</td>
                <td>{{ $journal->description }}</td>
                <td class="text-end">
                    <a href="{{ route('stores.journals.show', [$site, $journal]) }}" class="btn btn-info btn-sm">
                        <i class="bi bi-eye-fill"></i> Voir
                    </a>
                    <form action="{{ route('stores.journals.destroy', [$site, $journal]) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette transaction ?')">
                            <i class="bi bi-trash-fill"></i> Supprimer
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($journals instanceof \Illuminate\Pagination\LengthAwarePaginator)
        {{ $journals->links() }}
    @endif
</div>
@endsection
