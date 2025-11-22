@extends('layouts.app')

@section('content')
<h1 class="mt-4 mb-4">@t('Tableau de bord')</h1>

<!-- Tableau des alertes produits -->
@if(($productsWithoutImages ?? 0) > 0 || ($productsWithoutDescriptionFr ?? 0) > 0 || ($productsWithoutDescriptionEn ?? 0) > 0 || ($productsOutOfStock ?? 0) > 0 || ($productsWithFakeOrEmptyEan ?? 0) > 0 || ($productsWithoutCategories ?? 0) > 0)
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-danger">
            <i class="bi bi-exclamation-triangle"></i> @t('Produits avec problèmes')
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>@t('Type de problème')</th>
                        <th class="text-center" style="width: 150px;">@t('Nombre de produits')</th>
                        <th class="text-center" style="width: 150px;">@t('Actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @if(($productsWithoutImages ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-image text-warning"></i> @t('Produits sans images')
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark fs-5">{{ $productsWithoutImages }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-warning" href="{{ route('dashboard.products-issues', ['type' => 'no_image']) }}">
                                <i class="bi bi-eye"></i> @t('Voir les produits')
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsWithoutDescriptionFr ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-file-text text-danger"></i> @t('Produits sans description (FR)')
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger fs-5">{{ $productsWithoutDescriptionFr }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-danger" href="{{ route('dashboard.products-issues', ['type' => 'no_description_fr']) }}">
                                <i class="bi bi-eye"></i> @t('Voir les produits')
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsWithoutDescriptionEn ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-file-text text-info"></i> @t('Produits sans description (EN)')
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info fs-5">{{ $productsWithoutDescriptionEn }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-info" href="{{ route('dashboard.products-issues', ['type' => 'no_description_en']) }}">
                                <i class="bi bi-eye"></i> @t('Voir les produits')
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsOutOfStock ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-box-seam text-danger"></i> @t('Produits en rupture de stock')
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger fs-5">{{ $productsOutOfStock }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-danger" href="{{ route('stocks.index') }}">
                                <i class="bi bi-eye"></i> @t('Voir le stock')
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsWithFakeOrEmptyEan ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-upc-scan text-warning"></i> @t('Produits avec EAN fake ou vide')
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark fs-5">{{ $productsWithFakeOrEmptyEan }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-warning" href="{{ route('dashboard.products-issues', ['type' => 'fake_or_empty_ean']) }}">
                                <i class="bi bi-eye"></i> @t('Voir les produits')
                            </a>
                        </td>
                    </tr>
                    @endif

                    @if(($productsWithoutCategories ?? 0) > 0)
                    <tr>
                        <td>
                            <i class="bi bi-bookmarks text-primary"></i> @t('Produits sans catégorie')
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-5">{{ $productsWithoutCategories }}</span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-primary" href="{{ route('dashboard.products-issues', ['type' => 'no_category']) }}">
                                <i class="bi bi-eye"></i> @t('Voir les produits')
                            </a>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Cartes KPI originales -->
<div class="row">
    <!-- Carte KPI: Factures à payer -->
    <div class="col-md-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body d-flex flex-column justify-content-between h-100">
                <div>
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size:1.5em;">
                        @t('Factures à payer')
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:5em;">
                        {{ $invoicesToPayCount ?? 0 }}
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:1em">
                        @t('Montant total') : ${{ number_format($invoicesToPayTotal ?? 0, 2) }}
                    </div>
                </div>
                <div class="text-end">
                    <a class="btn btn-success" href="{{ route('stocks.index') }}">@t('Voir')</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte KPI: C.A. Siem Reap -->
    <div class="col-md-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size:1.5em;">
                            @t('C.A. Siem Reap')
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:5em;">$155</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" style="font-size:1em">
                            @t('Nombre de clients'): 15
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte KPI: C.A. Phnom Penh -->
    <div class="col-md-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size:1.5em;">
                            @t('C.A. Phnom Penh')
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
                @t('Factures par statut') (exemple)
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
                @t("Chiffre d'affaires mensuel") (exemple)
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
            labels: ["@t('À payer')", '@t('Payé')', '@t('Remboursé')', '@t('Annulé')'],
            datasets: [{
                label: '@t('Nombre de factures')',
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
