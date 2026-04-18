@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title mb-4"><i class="bi bi-file-earmark-bar-graph"></i> {{ __('messages.bilan.title') }}</h1>

    {{-- Store/Month Selector --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('financial.bilan') }}" class="row g-3 align-items-end">
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

    @if($store && isset($incomeStatement))

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
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.bilan.income_statement_title', ['month' => $monthLabel]) }}</h5>
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-6"><small>Date:{{ $endDate->format('d-F-Y') }}</small></div>
                <div class="col-6 text-end"><strong>{{ __('messages.bilan.exchange_rate') }}: {{ number_format($exchangeRate) }}</strong></div>
            </div>

            <table class="table table-bordered">
                <thead>
                    <tr class="table-light">
                        <th>{{ __('messages.bilan.description') }}</th>
                        <th class="text-end" style="width: 180px;">{{ __('messages.bilan.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Revenue --}}
                    <tr class="table-success">
                        <td><strong>({{ __('messages.bilan.revenue') }})</strong></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="ps-4">- {{ __('messages.bilan.sale_revenue') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['saleRevenue'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4">- {{ __('messages.bilan.discount') }}</td>
                        <td class="text-end"></td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <td>{{ __('messages.bilan.total_revenue') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['netRevenue'], 2) }}</td>
                    </tr>

                    {{-- Expenses --}}
                    <tr class="table-warning">
                        <td><strong>{{ __('messages.bilan.expense') }}</strong></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.salary_expense') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['salaryExpense'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.supplier_expense') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['supplierExpense'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.utility_expense') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['utilityExpense'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.supply_expense') }}</td>
                        <td class="text-end">{{ $incomeStatement['supplyExpense'] > 0 ? '$ ' . number_format($incomeStatement['supplyExpense'], 2) : '' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.rental_expense') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['rentalExpense'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.prepaid_internet') }}</td>
                        <td class="text-end">$ -</td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.maintenance_repairs') }}</td>
                        <td class="text-end">$ -</td>
                    </tr>
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.other_expense') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['otherExpense'], 2) }}</td>
                    </tr>
                    @if($incomeStatement['taxExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.tax_expense') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['taxExpense'], 2) }}</td>
                    </tr>
                    @endif
                    @if($incomeStatement['equipmentExpense'] > 0)
                    <tr>
                        <td class="ps-4">{{ __('messages.bilan.equipment_expense') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['equipmentExpense'], 2) }}</td>
                    </tr>
                    @endif
                    <tr class="table-light fw-bold">
                        <td>{{ __('messages.bilan.total_expense') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['totalExpense'], 2) }}</td>
                    </tr>

                    {{-- Net Profit/Loss --}}
                    <tr class="{{ $incomeStatement['netProfitLoss'] >= 0 ? 'table-success' : 'table-danger' }} fw-bold">
                        <td>{{ __('messages.bilan.net_profit_loss') }}</td>
                        <td class="text-end">$ {{ number_format($incomeStatement['netProfitLoss'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================= --}}
    {{-- 2. EXPENSE BOOK DAILY --}}
    {{-- ============================================= --}}
    <div class="card mb-4" id="expense-book">
        <div class="card-header text-center">
            <strong>{{ $store->name }}</strong>
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.bilan.expense_book_title', ['month' => $monthLabel]) }}</h5>
            <small class="text-end d-block">{{ __('messages.bilan.exchange_rate') }}: {{ number_format($exchangeRate) }} KHR/USD</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>{{ __('messages.bilan.date') }}</th>
                            <th>{{ __('messages.bilan.invoice_no') }}</th>
                            <th>{{ __('messages.bilan.vendor') }}</th>
                            <th>{{ __('messages.bilan.description') }}</th>
                            <th class="text-end">{{ __('messages.bilan.total_in_riel') }}</th>
                            <th class="text-end">{{ __('messages.bilan.total_in_dollar') }}</th>
                            <th>{{ __('messages.bilan.type_of_account') }}</th>
                            <th>{{ __('messages.bilan.note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenseBook['entries'] as $entry)
                        <tr>
                            <td>{{ $entry['date']->format('d-M-y') }}</td>
                            <td>{{ $entry['invoice_no'] }}</td>
                            <td>{{ $entry['vendor'] }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td class="text-end">{{ $entry['total_khr'] > 0 ? number_format($entry['total_khr']) . ' Riel' : '' }}</td>
                            <td class="text-end">{{ $entry['total_usd'] > 0 ? '$ ' . number_format($entry['total_usd'], 2) : '' }}</td>
                            <td>{{ $entry['account_type'] }}</td>
                            <td>{{ $entry['note'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted">{{ __('messages.bilan.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">{{ __('messages.bilan.total') }}:</td>
                            <td class="text-end">{{ $expenseBook['totalKhr'] > 0 ? number_format($expenseBook['totalKhr']) . ' Riel' : '' }}</td>
                            <td class="text-end">$ {{ number_format($expenseBook['totalUsd'], 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end">{{ __('messages.bilan.grand_total_dollar') }}</td>
                            <td colspan="2" class="text-end">$ {{ number_format($expenseBook['grandTotal'], 2) }}</td>
                            <td colspan="2"></td>
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
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.bilan.payroll_title', ['month' => $monthLabel]) }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr class="table-light">
                            <th rowspan="2" class="align-middle text-center" style="width:30px;">N&deg;</th>
                            <th rowspan="2" class="align-middle">{{ __('messages.bilan.name') }}</th>
                            <th rowspan="2" class="align-middle text-center">{{ __('messages.bilan.sex') }}</th>
                            <th rowspan="2" class="align-middle">{{ __('messages.bilan.position') }}</th>
                            <th rowspan="2" class="align-middle text-center">{{ __('messages.bilan.status') }}</th>
                            <th rowspan="2" class="align-middle text-center">{{ __('messages.bilan.day_off') }}</th>
                            <th rowspan="2" class="align-middle text-end">{{ __('messages.bilan.basic_salary') }}</th>
                            <th rowspan="2" class="align-middle text-end">{{ __('messages.bilan.overtime') }}</th>
                            <th rowspan="2" class="align-middle text-end">{{ __('messages.bilan.gasoline') }}</th>
                            <th rowspan="2" class="align-middle text-end">{{ __('messages.bilan.cm_reseller') }}</th>
                            <th rowspan="2" class="align-middle text-end">{{ __('messages.bilan.staff_borrow') }}</th>
                            <th colspan="2" class="text-center">{{ __('messages.bilan.deduction') }}</th>
                            <th rowspan="2" class="align-middle text-end">{{ __('messages.bilan.total_deduct') }}</th>
                            <th rowspan="2" class="align-middle text-end">{{ __('messages.bilan.total_amount') }}</th>
                        </tr>
                        <tr class="table-light">
                            <th class="text-end">{{ __('messages.bilan.leave') }}</th>
                            <th class="text-end">{{ __('messages.bilan.other') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrollReport['employees'] as $i => $emp)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>{{ $emp['name'] }}</td>
                            <td class="text-center">{{ $emp['sex'] }}</td>
                            <td>{{ $emp['position'] }}</td>
                            <td class="text-center">{{ ucfirst($emp['status']) }}</td>
                            <td class="text-center">{{ $emp['day_off'] }}</td>
                            <td class="text-end">$ {{ number_format($emp['base_salary'], 2) }}</td>
                            <td class="text-end">{{ $emp['overtime'] > 0 ? '$ ' . number_format($emp['overtime'], 2) : '$ -' }}</td>
                            <td class="text-end">{{ $emp['gasoline'] > 0 ? '$ ' . number_format($emp['gasoline'], 2) : '$ -' }}</td>
                            <td class="text-end">{{ $emp['cm_reseller'] > 0 ? '$ ' . number_format($emp['cm_reseller'], 2) : '$ -' }}</td>
                            <td class="text-end">{{ $emp['staff_borrow'] > 0 ? '$ ' . number_format($emp['staff_borrow'], 2) : '$ -' }}</td>
                            <td class="text-end">{{ $emp['deduction_leave'] > 0 ? '$ ' . number_format($emp['deduction_leave'], 2) : '$ -' }}</td>
                            <td class="text-end">{{ $emp['deduction_other'] > 0 ? '$ ' . number_format($emp['deduction_other'], 2) : '$ -' }}</td>
                            <td class="text-end">{{ $emp['total_deduct'] > 0 ? '$ ' . number_format($emp['total_deduct'], 2) : '$ -' }}</td>
                            <td class="text-end"><strong>$ {{ number_format($emp['total_amount'], 2) }}</strong></td>
                        </tr>
                        @empty
                        <tr><td colspan="15" class="text-center text-muted">{{ __('messages.bilan.no_payroll_data') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="14" class="text-center"><em>Total</em></td>
                            <td class="text-end text-decoration-underline">$ {{ number_format($payrollReport['totalAmount'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================= --}}
    {{-- 4. MONTHLY SALES REPORT --}}
    {{-- ============================================= --}}
    <div class="card mb-4" id="sales-report">
        <div class="card-header text-center">
            <strong>{{ $store->name }}</strong>
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.bilan.sales_report_title') }}</h5>
            <small>{{ __('messages.bilan.for_period', ['from' => $startDate->format('d-F-Y'), 'to' => $endDate->format('d-F-Y')]) }}</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr class="table-light">
                            <th style="width:40px;">No</th>
                            <th>{{ __('messages.bilan.date') }}</th>
                            <th>{{ __('messages.bilan.customer_name') }}</th>
                            <th colspan="2" class="text-center">{{ __('messages.bilan.pay_by') }}</th>
                            <th class="text-end">{{ __('messages.bilan.total_gross_sale') }}</th>
                            <th>{{ __('messages.bilan.note') }}</th>
                        </tr>
                        <tr class="table-light">
                            <th colspan="3"></th>
                            <th class="text-end">ABA</th>
                            <th class="text-end">Cash</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse($salesReport['entries'] as $brand => $data)
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>{{ $endDate->format('d-M-y') }}</td>
                            <td><strong>{{ $brand }}</strong></td>
                            <td class="text-end">{{ $data['aba'] > 0 ? '$ ' . number_format($data['aba'], 2) : '$ -' }}</td>
                            <td class="text-end">{{ $data['cash'] > 0 ? '$ ' . number_format($data['cash'], 2) : '$ -' }}</td>
                            <td class="text-end">$ {{ number_format($data['total'], 2) }}</td>
                            <td></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('messages.bilan.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">{{ __('messages.bilan.total') }}:</td>
                            <td class="text-end">$ {{ number_format($salesReport['totalAba'], 2) }}</td>
                            <td class="text-end">$ {{ number_format($salesReport['totalCash'], 2) }}</td>
                            <td class="text-end">$ {{ number_format($salesReport['grandTotal'], 2) }}</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end">{{ __('messages.bilan.total_income_cash_aba') }}</td>
                            <td colspan="4" class="text-start"><strong>$ {{ number_format($salesReport['grandTotal'], 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================= --}}
    {{-- 5. MONTHLY EXPENSE TO SUPPLIER --}}
    {{-- ============================================= --}}
    <div class="card mb-4" id="supplier-expense">
        <div class="card-header text-center">
            <strong>{{ $store->name }}</strong>
            <h5 class="mb-0 text-decoration-underline">{{ __('messages.bilan.supplier_expense_title', ['month' => $monthLabel]) }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr class="table-warning">
                            <th style="width:40px;">No</th>
                            <th>{{ __('messages.bilan.invoice_date') }}</th>
                            <th>{{ __('messages.bilan.invoice_no') }}</th>
                            <th>{{ __('messages.bilan.supplier_name') }}</th>
                            <th class="text-end">{{ __('messages.bilan.total_in_dollar') }}</th>
                            <th class="text-end">{{ __('messages.bilan.paid_amount') }}</th>
                            <th class="text-end">{{ __('messages.bilan.remaining_amount') }}</th>
                            <th>{{ __('messages.bilan.date_paid') }}</th>
                            <th>{{ __('messages.bilan.note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplierExpenseReport['entries'] as $i => $entry)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $entry['invoice_date'] ? $entry['invoice_date']->format('d-M-y') : '' }}</td>
                            <td>{{ $entry['invoice_no'] }}</td>
                            <td><strong>{{ $entry['supplier_name'] }}</strong></td>
                            <td class="text-end">$ {{ number_format($entry['amount'], 2) }}</td>
                            <td class="text-end">{{ $entry['paid_amount'] > 0 ? '$ ' . number_format($entry['paid_amount'], 2) : '$ -' }}</td>
                            <td class="text-end">{{ $entry['remaining'] > 0 ? '$ ' . number_format($entry['remaining'], 2) : '$ -' }}</td>
                            <td>{{ $entry['date_paid'] ? Carbon\Carbon::parse($entry['date_paid'])->format('d-M-y') : '' }}</td>
                            <td>{{ $entry['note'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">{{ __('messages.bilan.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">Total</td>
                            <td></td>
                            <td class="text-end">$ {{ number_format($supplierExpenseReport['total'], 2) }}</td>
                            <td class="text-end">$ -</td>
                            <td class="text-end">$ -</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end">{{ __('messages.bilan.total_supplier_expense') }}</td>
                            <td colspan="6"><strong>$ {{ number_format($supplierExpenseReport['total'], 2) }}</strong></td>
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
    #income-statement, #expense-book, #payroll-report, #sales-report, #supplier-expense {
        page-break-after: always;
    }
    body { font-size: 11px; }
}
</style>
@endsection
