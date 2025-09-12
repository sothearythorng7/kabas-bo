@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h1 class="crud_title">Tableau de bord - {{ $site->name }}</h1>

    <!-- Onglets Bootstrap qui pointent vers les pages correspondantes -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('stores.dashboard.index', $site) }}">Informations générales</a>
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
            <a class="nav-link" href="{{ route('stores.expense-categories.index', $site) }}">Catégories</a>
        </li>
    </ul>

    <!-- Contenu du dashboard général -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Chiffre d'affaires ce mois</h5>
                    <p class="card-text fs-3">{{ number_format($revenue, 2) }} €</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Dépenses totales ce mois</h5>
                    <p class="card-text fs-3">{{ number_format($totalExpenses, 2) }} €</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Solde net</h5>
                    <p class="card-text fs-3">{{ number_format($net, 2) }} €</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
