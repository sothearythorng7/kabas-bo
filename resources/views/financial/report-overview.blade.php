@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title mb-4"><i class="bi bi-building"></i> {{ __('messages.financial_overview.title') }}</h1>

    {{-- Month Selector --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('financial.report-overview') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="month" class="form-label">{{ __('messages.bilan.month') }}</label>
                    <input type="month" name="month" id="month" class="form-control" value="{{ $month }}" required>
                </div>
                <div class="col-md-4">
                    <label for="exchange_rate" class="form-label">{{ __('messages.bilan.exchange_rate') }}</label>
                    <div class="input-group">
                        <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" value="{{ $exchangeRate }}" min="1">
                        <span class="input-group-text">KHR/USD</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-calculator"></i> {{ __('messages.bilan.generate') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($data)

    {{-- Alerts --}}
    @if(!empty($data['alerts']))
    <div class="alert alert-warning d-print-none">
        <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> {{ __('messages.financial_report.alerts_title') }}</h6>
        <ul class="mb-0">
            @foreach($data['alerts'] as $alert)
                @if($alert['type'] === 'reseller_report_missing')
                    <li>{{ __('messages.financial_report.missing_reseller_report', ['name' => $alert['reseller']->name]) }}</li>
                @endif
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Print button --}}
    <div class="d-flex justify-content-end mb-3 d-print-none">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> {{ __('messages.btn.print') }}
        </button>
    </div>

    {{-- ============================================= --}}
    {{-- CONSOLIDATED INCOME STATEMENT --}}
    {{-- ============================================= --}}
    <div class="card mb-4" id="income-statement">
        <div class="card-header text-center">
            <strong>KABAS CONCEPT STORE</strong>
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.financial_overview.consolidated_title', ['month' => $data['monthLabel']]) }}</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr class="table-light">
                        <th>{{ __('messages.bilan.description') }}</th>
                        <th class="text-end" style="width: 160px;">{{ __('messages.bilan.amount') }}</th>
                        <th class="text-end" style="width: 140px;">{{ __('messages.bilan.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- === REVENUE === --}}
                    <tr class="table-success">
                        <td colspan="3"><strong>{{ __('messages.bilan.revenue') }}</strong></td>
                    </tr>

                    {{-- POS Revenue --}}
                    <tr>
                        <td class="ps-4">
                            <i class="bi bi-shop"></i> {{ __('messages.financial_report.pos_revenue') }}
                            <small class="text-muted">({{ number_format($data['posCount']) }} {{ __('messages.financial_report.sales') }})</small>
                        </td>
                        <td class="text-end">$ {{ number_format($data['posRevenue'], 2) }}</td>
                        <td></td>
                    </tr>
                    @foreach($data['posRevenueByStore'] as $storeData)
                        @if($storeData['revenue'] > 0)
                        <tr>
                            <td class="ps-5 text-muted small">↳ {{ $storeData['name'] }} ({{ $storeData['count'] }})</td>
                            <td class="text-end text-muted small">$ {{ number_format($storeData['revenue'], 2) }}</td>
                            <td></td>
                        </tr>
                        @endif
                    @endforeach

                    {{-- Website Revenue --}}
                    <tr>
                        <td class="ps-4">
                            <i class="bi bi-globe"></i> {{ __('messages.financial_report.website_revenue') }}
                            <small class="text-muted">({{ number_format($data['websiteCount']) }} {{ __('messages.financial_report.orders') }})</small>
                        </td>
                        <td class="text-end">$ {{ number_format($data['websiteRevenue'], 2) }}</td>
                        <td></td>
                    </tr>

                    {{-- Special Orders --}}
                    @if($data['specialRevenue'] > 0)
                    <tr>
                        <td class="ps-4">
                            <i class="bi bi-clipboard2-check"></i> {{ __('messages.financial_report.special_order_revenue') }}
                            <small class="text-muted">({{ $data['specialCount'] }})</small>
                        </td>
                        <td class="text-end">$ {{ number_format($data['specialRevenue'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif

                    {{-- Reseller Revenue --}}
                    <tr>
                        <td class="ps-4">
                            <i class="bi bi-people"></i> {{ __('messages.financial_report.reseller_revenue') }}
                        </td>
                        <td class="text-end">$ {{ number_format($data['resellerRevenue'], 2) }}</td>
                        <td></td>
                    </tr>
                    @foreach($data['resellerDetails'] as $resellerName => $resellerAmount)
                    <tr>
                        <td class="ps-5 text-muted small">↳ {{ $resellerName }}</td>
                        <td class="text-end text-muted small">$ {{ number_format($resellerAmount, 2) }}</td>
                        <td></td>
                    </tr>
                    @endforeach

                    {{-- Total Revenue --}}
                    <tr class="table-light fw-bold">
                        <td>{{ __('messages.financial_report.total_revenue') }}</td>
                        <td></td>
                        <td class="text-end">$ {{ number_format($data['totalRevenue'], 2) }}</td>
                    </tr>

                    {{-- === EXPENSES === --}}
                    <tr class="table-warning">
                        <td colspan="3"><strong>{{ __('messages.bilan.expense') }}</strong></td>
                    </tr>

                    {{-- Salary --}}
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.salary_expense') }}</td>
                        <td class="text-end">$ {{ number_format($data['salaryExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @foreach($data['salaryByStore'] as $storeData)
                        @if($storeData['amount'] > 0)
                        <tr>
                            <td class="ps-5 text-muted small">↳ {{ $storeData['name'] }}</td>
                            <td class="text-end text-muted small">$ {{ number_format($storeData['amount'], 2) }}</td>
                            <td></td>
                        </tr>
                        @endif
                    @endforeach

                    {{-- Supplier --}}
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.supplier_expense') }}</td>
                        <td class="text-end">$ {{ number_format($data['supplierExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @if($data['supplierExpenseBuyer'] > 0 || $data['supplierExpenseConsignment'] > 0)
                    <tr>
                        <td class="ps-5 text-muted small">↳ {{ __('messages.financial_report.buyer_suppliers') }}</td>
                        <td class="text-end text-muted small">$ {{ number_format($data['supplierExpenseBuyer'], 2) }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="ps-5 text-muted small">↳ {{ __('messages.financial_report.consignment_suppliers') }}</td>
                        <td class="text-end text-muted small">$ {{ number_format($data['supplierExpenseConsignment'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif

                    {{-- Operational --}}
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.rental_expense') }}</td>
                        <td class="text-end">$ {{ number_format($data['rentalExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.utility_expense') }}</td>
                        <td class="text-end">$ {{ number_format($data['utilityExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @if($data['supplyExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.supply_expense') }}</td>
                        <td class="text-end">$ {{ number_format($data['supplyExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($data['taxExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.tax_expense') }}</td>
                        <td class="text-end">$ {{ number_format($data['taxExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($data['equipmentExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.equipment_expense') }}</td>
                        <td class="text-end">$ {{ number_format($data['equipmentExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($data['otherExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.other_expense') }}</td>
                        <td class="text-end">$ {{ number_format($data['otherExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif

                    {{-- Total Expenses --}}
                    <tr class="table-light fw-bold">
                        <td>{{ __('messages.financial_report.total_expenses') }}</td>
                        <td></td>
                        <td class="text-end">$ {{ number_format($data['totalExpense'], 2) }}</td>
                    </tr>

                    {{-- Net Profit/Loss --}}
                    <tr class="{{ $data['netProfitLoss'] >= 0 ? 'table-success' : 'table-danger' }} fw-bold fs-5">
                        <td>{{ __('messages.bilan.net_profit_loss') }}</td>
                        <td></td>
                        <td class="text-end">$ {{ number_format($data['netProfitLoss'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================= --}}
    {{-- SUMMARY CARDS --}}
    {{-- ============================================= --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">{{ __('messages.financial_report.total_revenue') }}</div>
                    <div class="h4 mb-0 font-weight-bold">$ {{ number_format($data['totalRevenue'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">{{ __('messages.financial_report.total_expenses') }}</div>
                    <div class="h4 mb-0 font-weight-bold">$ {{ number_format($data['totalExpense'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow h-100 py-2" style="border-left: 4px solid {{ $data['netProfitLoss'] >= 0 ? '#1cc88a' : '#e74a3b' }};">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-1 {{ $data['netProfitLoss'] >= 0 ? 'text-success' : 'text-danger' }}">{{ __('messages.bilan.net_profit_loss') }}</div>
                    <div class="h4 mb-0 font-weight-bold {{ $data['netProfitLoss'] >= 0 ? 'text-success' : 'text-danger' }}">$ {{ number_format($data['netProfitLoss'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('messages.financial_overview.margin_rate') }}</div>
                    <div class="h4 mb-0 font-weight-bold">{{ $data['totalRevenue'] > 0 ? number_format(($data['netProfitLoss'] / $data['totalRevenue']) * 100, 1) : 0 }}%</div>
                </div>
            </div>
        </div>
    </div>

    @endif
</div>

<style>
@media print {
    .d-print-none, .navbar, .sidebar, #sidebar, .card-body form.row { display: none !important; }
    .card { border: 1px solid #000 !important; }
    body { font-size: 11px; }
}
</style>
@endsection
