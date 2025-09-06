@extends('layouts.app')

@section('content')
<h1 class="mt-4 mb-4">Tableau de bord</h1>

<div class="row">
    <!-- Carte KPI: Factures à payer -->
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body d-flex flex-column justify-content-between h-100">
                <div>
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size:1.5em;">
                        Factures à payer
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:5em;">
                        {{ $invoicesToPayCount ?? 0 }}
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:1em">
                        Montant total : ${{ number_format($invoicesToPayTotal ?? 0, 2) }}
                    </div>
                </div>
                <div class="text-end">
                    <a class="btn btn-success" href="{{ route('stocks.index') }}">Voir</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte KPI: Produits hors-stock -->
    <div class="col-md-3">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body d-flex flex-column justify-content-between h-100">
                <div>
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1" style="font-size:1.5em;">
                        Produits hors-stock
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:5em;">
                        {{ 3 }}
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <a class="btn btn-success" href="{{ route('stocks.index') }}">Voir</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte KPI: C.A. Siem Reap -->
    <div class="col-md-3">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size:1.5em;">
                            C.A. Siem Reap
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:5em;">$155</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:1em">
                            Nombre de clients: 15
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte KPI: C.A. Phnom Penh -->
    <div class="col-md-3">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size:1.5em;">
                            C.A. Phnom Penh
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:5em;">$155</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques côte à côte -->
<div class="row mt-4">
    <!-- Graphique à barres -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header">
                Factures par statut (exemple)
            </div>
            <div class="card-body">
                <canvas id="invoicesChart" width="400" height="150"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphique en ligne -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header">
                Chiffre d'affaires mensuel (exemple)
            </div>
            <div class="card-body">
                <canvas id="revenueChart" width="400" height="150"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Graphique à barres
    const ctxBar = document.getElementById('invoicesChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['À payer', 'Payé', 'Remboursé', 'Annulé'],
            datasets: [{
                label: 'Nombre de factures',
                data: [12, 8, 5, 2],
                backgroundColor: [
                    'rgba(78, 115, 223, 0.5)',
                    'rgba(28, 200, 138, 0.5)',
                    'rgba(246, 194, 62, 0.5)',
                    'rgba(231, 74, 59, 0.5)'
                ],
                borderColor: [
                    'rgba(78, 115, 223, 1)',
                    'rgba(28, 200, 138, 1)',
                    'rgba(246, 194, 62, 1)',
                    'rgba(231, 74, 59, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Graphique en ligne
    const ctxLine = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
            datasets: [{
                label: 'CA (USD)',
                data: [1200, 1900, 1500, 2200, 1800, 2500],
                fill: false,
                borderColor: 'rgba(78, 115, 223, 1)',
                backgroundColor: 'rgba(78, 115, 223, 0.5)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: false } }
        }
    });
</script>
@endpush
