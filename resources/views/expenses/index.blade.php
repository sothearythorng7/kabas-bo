@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Dépenses - {{ $site->name }}</h1>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.dashboard.index', $site) }}">Informations générales</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.journals.index', $site) }}">Journaux</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.payments.index', $site) }}">Paiements fournisseurs</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('stores.expenses.index', $site) }}">Dépenses</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expense-categories.index', $site) }}">Catégories</a>
        </li>
    </ul>
    <a href="{{ route('stores.expenses.create', $site) }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> Ajouter une dépense
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Catégorie</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Montant</th>
                <th>Document</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
            <tr>
                <td>{{ $expense->category->name }}</td>
                <td>{{ $expense->name }}</td>
                <td>{{ $expense->description }}</td>
                <td>{{ number_format($expense->amount, 2, ',', ' ') }} €</td>
                <td>
                    @if($expense->document)
                        <a href="{{ Storage::url($expense->document) }}" target="_blank">Voir</a>
                    @else
                        -
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('stores.expenses.edit', [$site, $expense]) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil-fill"></i> Modifier
                    </a>
                    <form action="{{ route('stores.expenses.destroy', [$site, $expense]) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette dépense ?')">
                            <i class="bi bi-trash-fill"></i> Supprimer
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
