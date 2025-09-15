@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4 crud_title">@t("Méthodes de paiement")</h1>
    @include('financial.layouts.nav')
    <a href="{{ route('financial.payment-methods.create', $store->id) }}" class="btn btn-primary mb-3">
        @t("Nouvelle méthode")
    </a>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>@t("Nom")</th>
                <th>@t("Code")</th>
                <th>@t("actions")</th>
            </tr>
        </thead>
        <tbody>
        @forelse($methods as $m)
            <tr>
                <td>{{ $m->name }}</td>
                <td>{{ $m->code }}</td>
                <td class="text-end">
                    <a href="{{ route('financial.payment-methods.edit', [$store->id, $m->id]) }}" class="btn btn-sm btn-warning">Modifier</a>
                    <form method="POST" action="{{ route('financial.payment-methods.destroy', [$store->id, $m->id]) }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')">@t("Supprimer")</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center">@t("Aucune méthode")</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $methods->links() }}
</div>
@endsection
