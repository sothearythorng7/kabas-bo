@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Catégories de dépenses</h1>
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
            <a class="nav-link" href="{{ route('stores.expenses.index', $site) }}">Dépenses</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('stores.expense-categories.index', $site) }}">Catégories</a>
        </li>
    </ul>
    <a href="{{ route('stores.expense-categories.create', $site) }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> Ajouter une catégorie
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Description</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description }}</td>
                <td class="text-end">
                    <a href="{{ route('stores.expense-categories.edit', [$site, $category]) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil-fill"></i> Modifier
                    </a>
                    <form action="{{ route('stores.expense-categories.destroy', [$site, $category]) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette catégorie ?')">
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
