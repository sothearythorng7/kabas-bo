@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Paiements fournisseurs") - {{ $site->name }}</h1>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.dashboard.index', $site) }}">@t("Informations générales")</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.journals.index', $site) }}">@t("Journaux")</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('stores.payments.index', $site) }}">@t("Paiements fournisseurs")</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expenses.index', $site) }}">@t("Dépenses")</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expense-categories.index', $site) }}">@t("Catégories")</a>
        </li>
    </ul>
    <a href="{{ route('stores.payments.create', $site) }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> @t("Ajouter un paiement")
    </a>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th> {{-- Dropdown / Actions --}}
                <th>@t("Fournisseur")</th>
                <th>@t("Référence")</th>
                <th class="text-center">@t("Montant")</th>
                <th>@t("Date échéance")</th>
                <th>@t("Document")</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td class="text-center">
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownPayment{{ $payment->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownPayment{{ $payment->id }}">
                            <li>
                                <a class="dropdown-item" href="{{ route('stores.payments.edit', [$site, $payment]) }}">
                                    <i class="bi bi-pencil-fill"></i> @t("Modifier")
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('stores.payments.destroy', [$site, $payment]) }}" method="POST" onsubmit="return confirm('@t("Supprimer ce paiement ?")')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="bi bi-trash-fill"></i> @t("Supprimer")
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>{{ $payment->supplier_name }}</td>
                <td>{{ $payment->reference }}</td>
                <td class="text-center">{{ number_format($payment->amount, 2, ',', ' ') }} €</td>
                <td>{{ $payment->due_date?->format('d/m/Y') ?? '-' }}</td>
                <td>
                    @if($payment->document)
                        <a href="{{ Storage::url($payment->document) }}" target="_blank">Voir</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
