@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title mb-4">{{ __('messages.financial.dashboard') }} - {{ $store->name }}</h1>
    @include('financial.layouts.nav')

    @if($unpaidInvoicesCount > 0)
    <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
        <div>
            <strong>{{ __('messages.financial.unpaid_invoices') }} :</strong> {{ $unpaidInvoicesCount }}
            <br>
            <strong>{{ __('messages.financial.total_value') }} :</strong> {{ number_format($unpaidInvoicesTotal, 2) }} $
        </div>
    </div>
    @endif
    <!-- Résumé financier -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.financial.current_balance') }}</h5>
                    <p class="card-text display-6">{{ number_format($currentBalance, 2) }} $</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.financial.credits_this_month') }}</h5>
                    <p class="card-text display-6">{{ number_format($monthCredits, 2) }} $</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.financial.debits_this_month') }}</h5>
                    <p class="card-text display-6">{{ number_format($monthDebits, 2) }} $</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique avec filtre de période -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="periodSelect" class="form-label">{{ __('messages.financial.period') }}</label>
                    <select id="periodSelect" class="form-select">
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>{{ __('messages.financial.current_month') }}</option>
                        <option value="6months" {{ $period == '6months' ? 'selected' : '' }}>{{ __('messages.financial.last_6_months') }}</option>
                        <option value="all" {{ $period == 'all' ? 'selected' : '' }}>{{ __('messages.financial.all') }}</option>
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
                    <h4 class="card-title mb-3">{{ __('messages.financial.top_5_accounts') }}</h4>
                    <ul class="list-group list-group-flush">
                        @foreach($topAccounts as $acc)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $acc->name }}
                                <span>{{ number_format($acc->total, 2) }} $</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-3">{{ __('messages.financial.payment_distribution') }}</h4>
                    <ul class="list-group list-group-flush">
                        @foreach($paymentDistribution as $method => $amount)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $method }}
                                <span>{{ number_format($amount, 2) }} $</span>
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
                    label: '{{ __('messages.financial.credits') }}',
                    data: @json($credits),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    type: 'bar',
                },
                {
                    label: '{{ __('messages.financial.debits') }}',
                    data: @json($debits),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    type: 'bar',
                },
                {
                    label: '{{ __('messages.financial.balance') }}',
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
                y: { type: 'linear', position: 'left', title: { display: true, text: 'Montants ($)' } },
                y1: { type: 'linear', position: 'right', title: { display: true, text: 'Solde ($)' }, grid: { drawOnChartArea: false } }
            }
        }
    });
});
</script>
@endpush
