@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title mb-4">@lang('Dashboard financier') - {{ $store->name }}</h1>
    @include('financial.layouts.nav')

    <!-- Résumé financier -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">@t("Solde actuel")</h5>
                    <p class="card-text display-6">{{ number_format($currentBalance, 2) }} €</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">@t("Entrées ce mois")</h5>
                    <p class="card-text display-6">{{ number_format($monthCredits, 2) }} €</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">@t("Sorties ce mois")</h5>
                    <p class="card-text display-6">{{ number_format($monthDebits, 2) }} €</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique avec filtre de période -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="periodSelect" class="form-label">@t("Période")</label>
                    <select id="periodSelect" class="form-select">
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>@t("Mois en cours")</option>
                        <option value="6months" {{ $period == '6months' ? 'selected' : '' }}>@t("6 derniers mois")</option>
                        <option value="all" {{ $period == 'all' ? 'selected' : '' }}>@t("Tout")</option>
                    </select>
                </div>
            </div>
            <canvas id="financeChart" height="100"></canvas>
        </div>
    </div>

    <!-- Top comptes et méthodes de paiement -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-3">@t("Top 5 comptes utilisés")</h4>
                    <ul class="list-group list-group-flush">
                        @foreach($topAccounts as $acc)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $acc->name }}
                                <span>{{ number_format($acc->total, 2) }} €</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-3">@t("Répartition par méthode de paiement")</h4>
                    <ul class="list-group list-group-flush">
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
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du select période
    const periodSelect = document.getElementById('periodSelect');
    if (periodSelect) {
        periodSelect.addEventListener('change', function() {
            const period = this.value;
            window.location.href = '{{ route("financial.dashboard", $store->id) }}?period=' + period;
        });
    }

    // Chart.js
    const ctx = document.getElementById('financeChart').getContext('2d');
    const financeChart = new Chart(ctx, {
        data: {
            labels: @json($dates),
            datasets: [
                {
                    label: '@t("Entrées")',
                    data: @json($credits),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    type: 'bar',
                },
                {
                    label: '@t("Sorties")',
                    data: @json($debits),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    type: 'bar',
                },
                {
                    label: '@t("Solde")',
                    data: @json($balancePerDay),
                    type: 'line',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.2,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            stacked: false,
            scales: {
                y: { type: 'linear', position: 'left', title: { display: true, text: 'Montants (€)' } },
                y1: { type: 'linear', position: 'right', title: { display: true, text: 'Solde (€)' }, grid: { drawOnChartArea: false } }
            }
        }
    });
});
</script>
@endpush
