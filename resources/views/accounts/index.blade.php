@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Comptes - {{ $site->name }}</h1>
    <a href="{{ route('stores.accounts.create', $site) }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> Ajouter un compte
    </a>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Type</th>
                <th>Solde</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $account)
            <tr>
                <td>{{ $account->name }}</td>
                <td>{{ ucfirst($account->type) }}</td>
                <td>{{ number_format($account->balance, 2, ',', ' ') }} $</td>
                <td class="text-start">
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="accountDropdown{{ $account->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="accountDropdown{{ $account->id }}">
                            <li>
                                <a class="dropdown-item" href="{{ route('stores.accounts.edit', [$site, $account]) }}">
                                    <i class="bi bi-pencil-fill"></i> Modifier
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('stores.accounts.destroy', [$site, $account]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button class="dropdown-item" type="submit" onclick="return confirm('Supprimer ce compte ?')">
                                        <i class="bi bi-trash-fill"></i> Supprimer
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
