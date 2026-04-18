@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title mb-4"><i class="bi bi-file-earmark-bar-graph"></i> {{ __('messages.financial_report.title') }}</h1>

    {{-- Store/Month Selector --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('financial.report') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="store_id" class="form-label">{{ __('messages.bilan.store') }}</label>
                    <select name="store_id" id="store_id" class="form-select" required>
                        <option value="">-- {{ __('messages.bilan.select_store') }} --</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $store && $store->id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="month" class="form-label">{{ __('messages.bilan.month') }}</label>
                    <input type="month" name="month" id="month" class="form-control" value="{{ $month }}" required>
                </div>
                <div class="col-md-3">
                    <label for="exchange_rate" class="form-label">{{ __('messages.bilan.exchange_rate') }}</label>
                    <div class="input-group">
                        <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" value="{{ $exchangeRate }}" min="1">
                        <span class="input-group-text">KHR/USD</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-calculator"></i> {{ __('messages.bilan.generate') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($store && isset($revenue))

    {{-- Alerts --}}
    @if(!empty($alerts))
    <div class="alert alert-warning d-print-none">
        <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> {{ __('messages.financial_report.alerts_title') }}</h6>
        <ul class="mb-0">
            @foreach($alerts as $alert)
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
    {{-- 1. INCOME STATEMENT --}}
    {{-- ============================================= --}}
    <div class="card mb-4" id="income-statement">
        <div class="card-header text-center">
            <strong>{{ strtoupper($store->name) }}</strong>
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.financial_report.income_statement_title', ['month' => $monthLabel]) }}</h5>
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-6"><small>{{ __('messages.bilan.date') }}: {{ $endDate->format('d-F-Y') }}</small></div>
                <div class="col-6 text-end"><strong>{{ __('messages.bilan.exchange_rate') }}: {{ number_format($exchangeRate) }} KHR/USD</strong></div>
            </div>

            <table class="table table-bordered">
                <thead>
                    <tr class="table-light">
                        <th>{{ __('messages.bilan.description') }}</th>
                        <th class="text-end" style="width: 160px;">{{ __('messages.bilan.amount') }}</th>
                        <th class="text-end" style="width: 100px;"></th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Revenue --}}
                    <tr class="table-success">
                        <td colspan="3"><strong>{{ __('messages.bilan.revenue') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="ps-4">
                            <i class="bi bi-shop"></i> {{ __('messages.financial_report.pos_revenue') }}
                            <small class="text-muted">({{ $revenue['posCount'] }} {{ __('messages.financial_report.sales') }})</small>
                        </td>
                        <td class="text-end">$ {{ number_format($revenue['posRevenue'], 2) }}</td>
                        <td></td>
                    </tr>
                    @if($revenue['websiteRevenue'] > 0 || $store->type === 'warehouse')
                    <tr>
                        <td class="ps-4">
                            <i class="bi bi-globe"></i> {{ __('messages.financial_report.website_revenue') }}
                            <small class="text-muted">({{ $revenue['websiteCount'] }} {{ __('messages.financial_report.orders') }})</small>
                        </td>
                        <td class="text-end">$ {{ number_format($revenue['websiteRevenue'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($revenue['specialRevenue'] > 0 || $store->type === 'warehouse')
                    <tr>
                        <td class="ps-4">
                            <i class="bi bi-clipboard2-check"></i> {{ __('messages.financial_report.special_order_revenue') }}
                            <small class="text-muted">({{ $revenue['specialCount'] }})</small>
                        </td>
                        <td class="text-end">$ {{ number_format($revenue['specialRevenue'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($revenue['resellerRevenue'] > 0 || $store->type === 'warehouse')
                    <tr>
                        <td class="ps-4">
                            <i class="bi bi-people"></i> {{ __('messages.financial_report.reseller_revenue') }}
                        </td>
                        <td class="text-end">$ {{ number_format($revenue['resellerRevenue'], 2) }}</td>
                        <td></td>
                    </tr>
                    @foreach($revenue['resellerDetails'] as $resellerName => $resellerAmount)
                    <tr>
                        <td class="ps-5 text-muted small">↳ {{ $resellerName }}</td>
                        <td class="text-end text-muted small">$ {{ number_format($resellerAmount, 2) }}</td>
                        <td></td>
                    </tr>
                    @endforeach
                    @endif
                    <tr class="table-light fw-bold">
                        <td>{{ __('messages.financial_report.total_revenue') }}</td>
                        <td></td>
                        <td class="text-end">$ {{ number_format($totalRevenue, 2) }}</td>
                    </tr>

                    {{-- Expenses --}}
                    <tr class="table-warning">
                        <td colspan="3"><strong>{{ __('messages.bilan.expense') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.salary_expense') }}</td>
                        <td class="text-end">$ {{ number_format($expenses['salaryExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="ps-4">
                            {{ __('messages.bilan.supplier_expense') }}
                        </td>
                        <td class="text-end">$ {{ number_format($expenses['supplierExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @if($expenses['supplierExpenseBuyer'] > 0 || $expenses['supplierExpenseConsignment'] > 0)
                    <tr>
                        <td class="ps-5 text-muted small">↳ {{ __('messages.financial_report.buyer_suppliers') }}</td>
                        <td class="text-end text-muted small">$ {{ number_format($expenses['supplierExpenseBuyer'], 2) }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="ps-5 text-muted small">↳ {{ __('messages.financial_report.consignment_suppliers') }}</td>
                        <td class="text-end text-muted small">$ {{ number_format($expenses['supplierExpenseConsignment'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.rental_expense') }}</td>
                        <td class="text-end">$ {{ number_format($expenses['rentalExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.utility_expense') }}</td>
                        <td class="text-end">$ {{ number_format($expenses['utilityExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @if($expenses['supplyExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.supply_expense') }}</td>
                        <td class="text-end">$ {{ number_format($expenses['supplyExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($expenses['taxExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.tax_expense') }}</td>
                        <td class="text-end">$ {{ number_format($expenses['taxExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($expenses['equipmentExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.equipment_expense') }}</td>
                        <td class="text-end">$ {{ number_format($expenses['equipmentExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($expenses['otherExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.other_expense') }}</td>
                        <td class="text-end">$ {{ number_format($expenses['otherExpense'], 2) }}</td>
                        <td></td>
                    </tr>
                    @endif
                    <tr class="table-light fw-bold">
                        <td>{{ __('messages.financial_report.total_expenses') }}</td>
                        <td></td>
                        <td class="text-end">$ {{ number_format($totalExpense, 2) }}</td>
                    </tr>

                    {{-- Net Profit/Loss --}}
                    <tr class="{{ $netProfitLoss >= 0 ? 'table-success' : 'table-danger' }} fw-bold">
                        <td>{{ __('messages.bilan.net_profit_loss') }}</td>
                        <td></td>
                        <td class="text-end">$ {{ number_format($netProfitLoss, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================= --}}
    {{-- 2. EXPENSE BOOK --}}
    {{-- ============================================= --}}
    <div class="card mb-4" id="expense-book">
        <div class="card-header text-center">
            <strong>{{ $store->name }}</strong>
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.financial_report.expense_book_title', ['month' => $monthLabel]) }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>{{ __('messages.financial_report.accounting_date') }}</th>
                            <th>{{ __('messages.financial_report.payment_date') }}</th>
                            <th>{{ __('messages.financial_report.category') }}</th>
                            <th>{{ __('messages.bilan.description') }}</th>
                            <th class="text-end">{{ __('messages.bilan.amount') }}</th>
                            <th>{{ __('messages.bilan.type_of_account') }}</th>
                            <th>{{ __('messages.common.status') }}</th>
                            <th>{{ __('messages.bilan.note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenseBook['entries'] as $i => $entry)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $entry['date']->format('d/m/Y') }}</td>
                            <td>{{ $entry['payment_date'] ? $entry['payment_date']->format('d/m/Y') : '-' }}</td>
                            <td>{{ $entry['category'] }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td class="text-end">$ {{ number_format($entry['amount'], 2) }}</td>
                            <td>{{ $entry['account_type'] }}</td>
                            <td>
                                @if($entry['status'] === 'paid')
                                    <span class="badge bg-success">{{ __('messages.Paid') }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ __('messages.order.pending') }}</span>
                                @endif
                            </td>
                            <td>{{ $entry['note'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">{{ __('messages.bilan.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">{{ __('messages.bilan.total') }}:</td>
                            <td class="text-end">$ {{ number_format($expenseBook['totalUsd'], 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================= --}}
    {{-- 3. STAFF PAYROLL REPORT --}}
    {{-- ============================================= --}}
    <div class="card mb-4" id="payroll-report">
        <div class="card-header text-center">
            <strong>{{ strtoupper($store->name) }}</strong>
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.financial_report.payroll_title', ['month' => $monthLabel]) }}</h5>
            <small class="text-muted">{{ __('messages.financial_report.payroll_note') }}</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr class="table-light">
                            <th>#</th>
                            <th>{{ __('messages.bilan.name') }}</th>
                            <th class="text-center">{{ __('messages.bilan.status') }}</th>
                            <th class="text-end">{{ __('messages.bilan.basic_salary') }}</th>
                            <th class="text-end">{{ __('messages.financial_report.additions') }}</th>
                            <th class="text-end">{{ __('messages.financial_report.deductions') }}</th>
                            <th class="text-end">{{ __('messages.financial_report.net_salary') }}</th>
                            <th class="text-center">{{ __('messages.financial_report.paid') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrollReport['employees'] as $i => $emp)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $emp['name'] }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $emp['status'] === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($emp['status']) }}</span>
                            </td>
                            <td class="text-end">$ {{ number_format($emp['base_salary'], 2) }}</td>
                            <td class="text-end">
                                @if($emp['total_additions'] > 0)
                                    <span class="text-success" title="OT: ${{ number_format($emp['overtime'], 2) }} | Bonus: ${{ number_format($emp['bonus'], 2) }} | CM: ${{ number_format($emp['commission'], 2) }}">
                                        + $ {{ number_format($emp['total_additions'], 2) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                @if($emp['total_deductions'] > 0)
                                    <span class="text-danger" title="Absences: ${{ number_format($emp['absence_deduction'], 2) }} | Avances: ${{ number_format($emp['advances_deduction'], 2) }} | Pénalités: ${{ number_format($emp['penalty'], 2) }}">
                                        - $ {{ number_format($emp['total_deductions'], 2) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end fw-bold">$ {{ number_format($emp['net_amount'], 2) }}</td>
                            <td class="text-center">
                                @if($emp['is_paid'])
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @else
                                    <i class="bi bi-clock text-warning"></i>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted">{{ __('messages.bilan.no_payroll_data') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold table-light">
                            <td colspan="6" class="text-end">{{ __('messages.bilan.total') }}:</td>
                            <td class="text-end">$ {{ number_format($payrollReport['totalAmount'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================= --}}
    {{-- 4. SUPPLIER EXPENSE DETAIL --}}
    {{-- ============================================= --}}
    <div class="card mb-4" id="supplier-expense">
        <div class="card-header text-center">
            <strong>{{ $store->name }}</strong>
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.financial_report.supplier_expense_title', ['month' => $monthLabel]) }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr class="table-warning">
                            <th>#</th>
                            <th>{{ __('messages.financial_report.type') }}</th>
                            <th>{{ __('messages.bilan.supplier_name') }}</th>
                            <th>{{ __('messages.financial_report.invoice_date') }}</th>
                            <th>{{ __('messages.financial_report.payment_date') }}</th>
                            <th>{{ __('messages.financial_report.reference') }}</th>
                            <th class="text-end">{{ __('messages.bilan.amount') }}</th>
                            <th class="text-center">{{ __('messages.financial_report.paid') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplierExpenseReport['entries'] as $i => $entry)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                @if($entry['type'] === 'buyer')
                                    <span class="badge bg-primary">{{ __('messages.financial_report.buyer') }}</span>
                                @else
                                    <span class="badge bg-info">{{ __('messages.financial_report.consignment') }}</span>
                                @endif
                            </td>
                            <td><strong>{{ $entry['supplier_name'] }}</strong></td>
                            <td>{{ $entry['invoice_date'] ? $entry['invoice_date']->format('d/m/Y') : '-' }}</td>
                            <td>{{ $entry['paid_at'] ? $entry['paid_at']->format('d/m/Y') : '-' }}</td>
                            <td>{{ $entry['reference'] }}</td>
                            <td class="text-end">$ {{ number_format($entry['amount'], 2) }}</td>
                            <td class="text-center">
                                @if($entry['type'] === 'buyer')
                                    @if($entry['is_paid'])
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @else
                                        <i class="bi bi-clock text-warning"></i>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted">{{ __('messages.bilan.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr>
                            <td colspan="6" class="text-end">{{ __('messages.bilan.total') }}:</td>
                            <td class="text-end">$ {{ number_format($supplierExpenseReport['total'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @endif
</div>

<style>
@media print {
    .d-print-none, .navbar, .sidebar, #sidebar, .card-body form.row { display: none !important; }
    .card { border: 1px solid #000 !important; page-break-inside: avoid; }
    #income-statement, #expense-book, #payroll-report, #supplier-expense {
        page-break-after: always;
    }
    body { font-size: 11px; }
}
</style>
@endsection
