@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4 crud_title">Comptes – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <a href="{{ route('financial.accounts.create', $store->id) }}" class="btn btn-primary mb-3">
        Nouveau compte
    </a>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Parent</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($accounts as $acc)
            <tr>
                <td>{{ $acc->code }}</td>
                <td>{{ $acc->name }}</td>
                <td>{{ $acc->type?->label() ?? '-' }}</td>
                <td>{{ $acc->parent?->name ?? '-' }}</td>
                <td class="text-end">
                    <a href="{{ route('financial.accounts.edit', [$store->id, $acc->id]) }}" class="btn btn-sm btn-warning">Modifier</a>
                    <form method="POST" action="{{ route('financial.accounts.destroy', [$store->id, $acc->id]) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center">Aucun compte</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $accounts->links() }}
</div>
@endsection
