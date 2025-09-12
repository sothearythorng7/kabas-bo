@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Comptes - {{ $site->name }}</h1>
    <a href="{{ route('stores.accounts.create', $site) }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> Ajouter un compte
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Type</th>
                <th>Solde</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $account)
            <tr>
                <td>{{ $account->name }}</td>
                <td>{{ ucfirst($account->type) }}</td>
                <td>{{ number_format($account->balance, 2, ',', ' ') }} €</td>
                <td class="text-end">
                    <a href="{{ route('stores.accounts.edit', [$site, $account]) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil-fill"></i> Modifier
                    </a>
                    <form action="{{ route('stores.accounts.destroy', [$site, $account]) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce compte ?')">
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
