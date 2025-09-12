@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Dashboard financier – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Solde actuel</h5>
                    <p class="card-text display-6">{{ number_format($currentBalance, 2) }} €</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Entrées ce mois</h5>
                    <p class="card-text display-6">{{ number_format($monthCredits, 2) }} €</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Sorties ce mois</h5>
                    <p class="card-text display-6">{{ number_format($monthDebits, 2) }} €</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <h4>Top 5 comptes utilisés</h4>
            <ul class="list-group mb-4">
                @foreach($topAccounts as $acc)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $acc->name }}
                        <span>{{ number_format($acc->total, 2) }} €</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="col-md-6">
            <h4>Répartition par méthode de paiement</h4>
            <ul class="list-group mb-4">
                @foreach($paymentDistribution as $method => $amount)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $method }}
                        <span>{{ number_format($amount, 2) }} €</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
