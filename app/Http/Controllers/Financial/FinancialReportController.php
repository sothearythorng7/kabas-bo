<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\GeneralInvoice;
use App\Models\Sale;
use App\Models\WebsiteOrder;
use App\Models\Reseller;
use App\Models\ResellerSalesReport;
use App\Models\SupplierOrder;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    private const EXCHANGE_RATE_DEFAULT = 4000;

    public function index(Request $request)
    {
        $stores = Store::orderBy('name')->get();
        $store = null;
        $month = $request->input('month', now()->subMonth()->format('Y-m'));
        $exchangeRate = (int) $request->input('exchange_rate', self::EXCHANGE_RATE_DEFAULT);

        if ($request->has('store_id') && $request->has('month')) {
            $store = Store::findOrFail($request->input('store_id'));
            $data = $this->generateReport($store, $month, $exchangeRate);

            return view('financial.report', array_merge(compact('stores', 'store', 'month', 'exchangeRate'), $data));
        }

        return view('financial.report', compact('stores', 'store', 'month', 'exchangeRate'));
    }

    /**
     * Overview: consolidated financial report across all stores.
     */
    public function overview(Request $request)
    {
        $month = $request->input('month', now()->subMonth()->format('Y-m'));
        $exchangeRate = (int) $request->input('exchange_rate', self::EXCHANGE_RATE_DEFAULT);
        $data = null;

        if ($request->has('month')) {
            $data = $this->generateOverview($month, $exchangeRate);
        }

        return view('financial.report-overview', array_merge(compact('month', 'exchangeRate'), ['data' => $data]));
    }

    private function generateOverview(string $month, int $exchangeRate): array
    {
        $year = (int) substr($month, 0, 4);
        $m = (int) substr($month, 5, 2);
        $startDate = Carbon::create($year, $m, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        $monthLabel = $startDate->translatedFormat('F Y');

        $stores = Store::all();
        $alerts = $this->checkMissingResellerReports($startDate, $endDate);

        // === REVENUE ===

        // POS Sales — all stores
        $allPosSales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();
        $posRevenue = Sale::sumRealRevenue($allPosSales);
        $posCount = $allPosSales->count();

        // POS per store
        $posRevenueByStore = [];
        foreach ($stores as $store) {
            $storeSales = $allPosSales->where('store_id', $store->id);
            $posRevenueByStore[$store->id] = [
                'name' => $store->name,
                'revenue' => Sale::sumRealRevenue($storeSales),
                'count' => $storeSales->count(),
            ];
        }

        // Website Orders
        $websiteRevenue = (float) WebsiteOrder::where('payment_status', 'paid')
            ->where('source', 'website')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');
        $websiteCount = WebsiteOrder::where('payment_status', 'paid')
            ->where('source', 'website')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->count();

        // Special Orders
        $specialRevenue = (float) WebsiteOrder::where('payment_status', 'paid')
            ->where('source', 'backoffice')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');
        $specialCount = WebsiteOrder::where('payment_status', 'paid')
            ->where('source', 'backoffice')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->count();

        // Reseller Sales
        $resellerReports = ResellerSalesReport::where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where('end_date', '>=', $startDate);
            })
            ->with(['items', 'reseller'])
            ->get();

        $resellerRevenue = 0;
        $resellerDetails = [];
        foreach ($resellerReports as $report) {
            $reportTotal = $report->items->sum(fn($item) => $item->quantity_sold * $item->unit_price);
            $resellerRevenue += $reportTotal;
            $resellerName = $report->reseller?->name ?? '-';
            $resellerDetails[$resellerName] = ($resellerDetails[$resellerName] ?? 0) + $reportTotal;
        }

        $totalRevenue = $posRevenue + $websiteRevenue + $specialRevenue + $resellerRevenue;

        // === EXPENSES ===

        // Salaries: paid at the beginning of the following month
        $payrollPeriod = $startDate->copy()->addMonth()->format('Y-m');

        $salaryExpense = (float) DB::table('salary_payments')
            ->where('period', $payrollPeriod)
            ->sum('net_amount');

        $salaryByStore = [];
        foreach ($stores as $store) {
            $salaryByStore[$store->id] = [
                'name' => $store->name,
                'amount' => (float) DB::table('salary_payments')
                    ->where('store_id', $store->id)
                    ->where('period', $payrollPeriod)
                    ->sum('net_amount'),
            ];
        }

        // Supplier expense — all stores
        $supplierExpenseBuyer = 0;
        $supplierExpenseConsignment = 0;
        foreach ($stores as $store) {
            $supplierExpenseBuyer += $this->getSupplierBuyerExpense($store, $startDate, $endDate);
            $supplierExpenseConsignment += $this->getSupplierConsignmentExpense($store, $startDate, $endDate);
        }
        $supplierExpense = $supplierExpenseBuyer + $supplierExpenseConsignment;

        // General invoices — all stores
        $expensesByAccount = GeneralInvoice::where(function ($q) use ($year, $m) {
                $q->where(function ($q2) use ($year, $m) {
                    $q2->whereNotNull('invoice_date')
                        ->whereYear('invoice_date', $year)
                        ->whereMonth('invoice_date', $m);
                })->orWhere(function ($q2) use ($year, $m) {
                    $q2->whereNull('invoice_date')
                        ->whereYear('due_date', $year)
                        ->whereMonth('due_date', $m);
                });
            })
            ->selectRaw('account_id, SUM(amount) as total')
            ->groupBy('account_id')
            ->pluck('total', 'account_id');

        $rentalExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50001')] ?? 0);
        $utilityExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50002')] ?? 0);
        $supplyExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50004')] ?? 0);
        $otherExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50005')] ?? 0);
        $taxExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50006')] ?? 0);
        $equipmentExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50007')] ?? 0);

        $totalOperational = $rentalExpense + $utilityExpense + $supplyExpense
            + $otherExpense + $taxExpense + $equipmentExpense;

        $totalExpense = $salaryExpense + $supplierExpense + $totalOperational;
        $netProfitLoss = $totalRevenue - $totalExpense;

        return compact(
            'monthLabel', 'startDate', 'endDate', 'stores', 'alerts',
            'posRevenue', 'posCount', 'posRevenueByStore',
            'websiteRevenue', 'websiteCount',
            'specialRevenue', 'specialCount',
            'resellerRevenue', 'resellerDetails',
            'totalRevenue',
            'salaryExpense', 'salaryByStore',
            'supplierExpense', 'supplierExpenseBuyer', 'supplierExpenseConsignment',
            'rentalExpense', 'utilityExpense', 'supplyExpense',
            'otherExpense', 'taxExpense', 'equipmentExpense',
            'totalOperational', 'totalExpense', 'netProfitLoss'
        );
    }

    private function generateReport(Store $store, string $month, int $exchangeRate): array
    {
        $year = (int) substr($month, 0, 4);
        $m = (int) substr($month, 5, 2);
        $startDate = Carbon::create($year, $m, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        $monthLabel = $startDate->translatedFormat('F Y');

        // Alerts: missing reseller reports
        $alerts = $this->checkMissingResellerReports($startDate, $endDate);

        // Revenue breakdown
        $revenue = $this->buildRevenue($store, $startDate, $endDate, $month);

        // Expenses breakdown
        // Salaries are paid at the beginning of the following month,
        // so March salaries have period=2026-04 (paid in April).
        $payrollPeriod = $startDate->copy()->addMonth()->format('Y-m');

        $expenses = $this->buildExpenses($store, $year, $m, $startDate, $endDate, $payrollPeriod);

        // Income statement summary
        $totalRevenue = $revenue['total'];
        $totalExpense = $expenses['total'];
        $netProfitLoss = $totalRevenue - $totalExpense;

        // Expense book (general invoices detail)
        $expenseBook = $this->buildExpenseBook($store, $year, $m, $exchangeRate);

        // Payroll detail
        $payrollReport = $this->buildPayrollReport($store, $payrollPeriod);

        // Supplier expense detail
        $supplierExpenseReport = $this->buildSupplierExpenseReport($store, $startDate, $endDate);

        return compact(
            'monthLabel', 'startDate', 'endDate', 'alerts',
            'revenue', 'expenses', 'totalRevenue', 'totalExpense', 'netProfitLoss',
            'expenseBook', 'payrollReport', 'supplierExpenseReport'
        );
    }

    /**
     * Check for missing reseller reports for the given month.
     */
    private function checkMissingResellerReports(Carbon $startDate, Carbon $endDate): array
    {
        $alerts = [];

        // All active resellers (consignment type have sales reports)
        $resellers = Reseller::where('type', 'consignment')->get();

        foreach ($resellers as $reseller) {
            // Check if a report covers this month
            $hasReport = ResellerSalesReport::where('reseller_id', $reseller->id)
                ->where(function ($q) use ($startDate, $endDate) {
                    // Report period overlaps the month
                    $q->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
                })
                ->exists();

            if (!$hasReport) {
                $alerts[] = [
                    'type' => 'reseller_report_missing',
                    'reseller' => $reseller,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Build revenue breakdown by source (accounting date logic).
     *
     * A. POS Sales: accounting_date = sale created_at
     * B. Website Orders: accounting_date = paid_at
     * C. Special Orders: accounting_date = paid_at (source = backoffice)
     * D. Reseller Sales: accounting_date = report period (start_date/end_date)
     */
    private function buildRevenue(Store $store, Carbon $startDate, Carbon $endDate, string $month): array
    {
        // A. POS Sales (created_at = accounting date)
        $posSales = Sale::where('store_id', $store->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        $posRevenue = Sale::sumRealRevenue($posSales);
        $posCount = $posSales->count();

        // B. Website Orders (paid_at = accounting date, source = website)
        $websiteRevenue = (float) WebsiteOrder::where('store_id', $store->id)
            ->where('payment_status', 'paid')
            ->where('source', 'website')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');
        $websiteCount = WebsiteOrder::where('store_id', $store->id)
            ->where('payment_status', 'paid')
            ->where('source', 'website')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->count();

        // C. Special Orders / Devis (paid_at = accounting date, source = backoffice)
        $specialRevenue = (float) WebsiteOrder::where('store_id', $store->id)
            ->where('payment_status', 'paid')
            ->where('source', 'backoffice')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');
        $specialCount = WebsiteOrder::where('store_id', $store->id)
            ->where('payment_status', 'paid')
            ->where('source', 'backoffice')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->count();

        // D. Reseller Sales (accounting_date = report period month)
        // Financial transactions for resellers are recorded on the Warehouse
        // Only show reseller revenue when viewing the Warehouse store
        $resellerReports = collect();
        if ($store->type === 'warehouse') {
            $resellerReports = ResellerSalesReport::where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
                })
                ->with(['items', 'reseller'])
                ->get();
        }

        $resellerRevenue = 0;
        $resellerDetails = [];
        foreach ($resellerReports as $report) {
            $reportTotal = $report->items->sum(fn($item) => $item->quantity_sold * $item->unit_price);
            $resellerRevenue += $reportTotal;

            $resellerName = $report->reseller?->name ?? '-';
            if (!isset($resellerDetails[$resellerName])) {
                $resellerDetails[$resellerName] = 0;
            }
            $resellerDetails[$resellerName] += $reportTotal;
        }

        $total = $posRevenue + $websiteRevenue + $specialRevenue + $resellerRevenue;

        return compact(
            'posRevenue', 'posCount',
            'websiteRevenue', 'websiteCount',
            'specialRevenue', 'specialCount',
            'resellerRevenue', 'resellerDetails',
            'total'
        );
    }

    /**
     * Build expenses breakdown (accounting date logic).
     *
     * A. Salaries & commissions: period = work month
     * B. Supplier invoices: paid_at date
     * C. General invoices / operational: invoice_date (fallback due_date)
     */
    private function buildExpenses(Store $store, int $year, int $m, Carbon $startDate, Carbon $endDate, string $payrollPeriod): array
    {
        // A. Salaries: paid at the beginning of the following month,
        // so $payrollPeriod is the payment month (work month + 1)
        $salaryExpense = (float) DB::table('salary_payments')
            ->where('store_id', $store->id)
            ->where('period', $payrollPeriod)
            ->sum('net_amount');

        // B. Supplier expense: buyer orders paid in this month + consignment cost
        $supplierExpenseBuyer = $this->getSupplierBuyerExpense($store, $startDate, $endDate);
        $supplierExpenseConsignment = $this->getSupplierConsignmentExpense($store, $startDate, $endDate);
        $supplierExpense = $supplierExpenseBuyer + $supplierExpenseConsignment;

        // C. General invoices grouped by account (invoice_date = accounting date, fallback due_date)
        $expensesByAccount = GeneralInvoice::where('store_id', $store->id)
            ->where(function ($q) use ($year, $m) {
                $q->where(function ($q2) use ($year, $m) {
                    $q2->whereNotNull('invoice_date')
                        ->whereYear('invoice_date', $year)
                        ->whereMonth('invoice_date', $m);
                })->orWhere(function ($q2) use ($year, $m) {
                    $q2->whereNull('invoice_date')
                        ->whereYear('due_date', $year)
                        ->whereMonth('due_date', $m);
                });
            })
            ->selectRaw('account_id, SUM(amount) as total')
            ->groupBy('account_id')
            ->pluck('total', 'account_id');

        $rentalExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50001')] ?? 0);
        $utilityExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50002')] ?? 0);
        $supplyExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50004')] ?? 0);
        $otherExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50005')] ?? 0);
        $taxExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50006')] ?? 0);
        $equipmentExpense = (float) ($expensesByAccount[FinancialAccount::idByCode('50007')] ?? 0);

        $totalOperational = $rentalExpense + $utilityExpense + $supplyExpense
            + $otherExpense + $taxExpense + $equipmentExpense;

        $total = $salaryExpense + $supplierExpense + $totalOperational;

        return compact(
            'salaryExpense', 'supplierExpense', 'supplierExpenseBuyer', 'supplierExpenseConsignment',
            'rentalExpense', 'utilityExpense', 'supplyExpense',
            'otherExpense', 'taxExpense', 'equipmentExpense',
            'totalOperational', 'total'
        );
    }

    /**
     * Buyer supplier orders invoiced in this period (invoice_date = accounting date).
     * Fallback to paid_at then created_at if invoice_date not set.
     */
    private function getSupplierBuyerExpense(Store $store, Carbon $startDate, Carbon $endDate): float
    {
        $orders = SupplierOrder::where('destination_store_id', $store->id)
            ->where('status', 'received')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNotNull('invoice_date')
                        ->whereBetween('invoice_date', [$startDate, $endDate]);
                })->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNull('invoice_date')
                        ->whereNotNull('paid_at')
                        ->whereBetween('paid_at', [$startDate, $endDate]);
                });
            })
            ->with('products', 'rawMaterials')
            ->get();

        $total = 0;
        foreach ($orders as $order) {
            if ($order->isProductOrder()) {
                $total += $order->products->sum(function ($p) {
                    return ($p->pivot->invoice_price ?? $p->pivot->purchase_price ?? 0)
                        * ($p->pivot->quantity_received ?? 0);
                });
            } else {
                $total += $order->rawMaterials->sum(function ($rm) {
                    return ($rm->pivot->invoice_price ?? $rm->pivot->purchase_price ?? 0)
                        * ($rm->pivot->quantity_received ?? 0);
                });
            }
        }

        return round($total, 5);
    }

    /**
     * Consignment supplier cost based on sales in the period.
     */
    private function getSupplierConsignmentExpense(Store $store, Carbon $startDate, Carbon $endDate): float
    {
        $saleIds = DB::table('sales')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('id');

        if ($saleIds->isEmpty()) return 0;

        $consignmentSuppliers = DB::table('suppliers')
            ->where('type', 'consignment')
            ->where('is_active', 1)
            ->get(['id', 'name']);

        $total = 0;
        foreach ($consignmentSuppliers as $supplier) {
            $productIds = DB::table('product_supplier')
                ->where('supplier_id', $supplier->id)
                ->pluck('product_id');

            if ($productIds->isEmpty()) continue;

            $items = DB::table('sale_items')
                ->whereIn('sale_id', $saleIds)
                ->whereIn('product_id', $productIds)
                ->whereNull('exchanged_at')
                ->get(['product_id', 'quantity']);

            foreach ($items as $item) {
                $purchasePrice = (float) DB::table('product_supplier')
                    ->where('product_id', $item->product_id)
                    ->where('supplier_id', $supplier->id)
                    ->value('purchase_price');
                $total += $purchasePrice * $item->quantity;
            }
        }

        return round($total, 5);
    }

    /**
     * Expense book detail (general invoices).
     */
    private function buildExpenseBook(Store $store, int $year, int $m, int $exchangeRate): array
    {
        $invoices = GeneralInvoice::where('store_id', $store->id)
            ->where(function ($q) use ($year, $m) {
                $q->where(function ($q2) use ($year, $m) {
                    $q2->whereNotNull('invoice_date')
                        ->whereYear('invoice_date', $year)
                        ->whereMonth('invoice_date', $m);
                })->orWhere(function ($q2) use ($year, $m) {
                    $q2->whereNull('invoice_date')
                        ->whereYear('due_date', $year)
                        ->whereMonth('due_date', $m);
                });
            })
            ->with('account', 'category')
            ->orderByRaw('COALESCE(invoice_date, due_date)')
            ->get();

        $totalUsd = 0;
        $entries = [];

        foreach ($invoices as $invoice) {
            $totalUsd += $invoice->amount;

            $entries[] = [
                'date' => Carbon::parse($invoice->invoice_date ?? $invoice->due_date),
                'payment_date' => $invoice->payment_date ? Carbon::parse($invoice->payment_date) : null,
                'category' => $invoice->category?->name ?? 'N/A',
                'description' => $invoice->label,
                'amount' => $invoice->amount,
                'account_type' => $invoice->account?->name ?? '-',
                'status' => $invoice->status,
                'note' => $invoice->note,
            ];
        }

        return compact('entries', 'totalUsd');
    }

    /**
     * Payroll detail for the month.
     */
    private function buildPayrollReport(Store $store, string $period): array
    {
        $payments = DB::table('salary_payments')
            ->where('store_id', $store->id)
            ->where('period', $period)
            ->get();

        $employees = [];
        $totalAmount = 0;

        foreach ($payments as $payment) {
            $staff = DB::table('staff_members')->where('id', $payment->staff_member_id)->first();

            $totalDeduct = $payment->absence_deduction + $payment->penalty_amount + $payment->advances_deduction;
            $totalAdditions = $payment->overtime_amount + $payment->bonus_amount
                + $payment->commission_amount + $payment->other_adjustment_amount;

            $employees[] = [
                'name' => $staff->name ?? 'Unknown',
                'status' => $staff->contract_status ?? '-',
                'base_salary' => $payment->base_salary,
                'overtime' => $payment->overtime_amount,
                'bonus' => $payment->bonus_amount,
                'commission' => $payment->commission_amount,
                'other_additions' => $payment->other_adjustment_amount,
                'total_additions' => $totalAdditions,
                'absence_deduction' => $payment->absence_deduction,
                'advances_deduction' => $payment->advances_deduction,
                'penalty' => $payment->penalty_amount,
                'total_deductions' => $totalDeduct,
                'net_amount' => $payment->net_amount,
                'is_paid' => $payment->financial_transaction_id !== null,
            ];

            $totalAmount += $payment->net_amount;
        }

        return compact('employees', 'totalAmount');
    }

    /**
     * Supplier expense detail.
     */
    private function buildSupplierExpenseReport(Store $store, Carbon $startDate, Carbon $endDate): array
    {
        $entries = [];
        $total = 0;

        // Buyer supplier orders invoiced in this period (invoice_date = accounting date)
        $orders = SupplierOrder::where('destination_store_id', $store->id)
            ->where('status', 'received')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNotNull('invoice_date')
                        ->whereBetween('invoice_date', [$startDate, $endDate]);
                })->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNull('invoice_date')
                        ->whereNotNull('paid_at')
                        ->whereBetween('paid_at', [$startDate, $endDate]);
                });
            })
            ->with(['supplier', 'products', 'rawMaterials'])
            ->get();

        foreach ($orders as $order) {
            $amount = 0;
            if ($order->isProductOrder()) {
                $amount = $order->products->sum(function ($p) {
                    return ($p->pivot->invoice_price ?? $p->pivot->purchase_price ?? 0)
                        * ($p->pivot->quantity_received ?? 0);
                });
            } else {
                $amount = $order->rawMaterials->sum(function ($rm) {
                    return ($rm->pivot->invoice_price ?? $rm->pivot->purchase_price ?? 0)
                        * ($rm->pivot->quantity_received ?? 0);
                });
            }

            $accountingDate = $order->invoice_date ?? $order->paid_at;

            $entries[] = [
                'type' => 'buyer',
                'supplier_name' => $order->supplier->name ?? '-',
                'invoice_date' => $accountingDate ? Carbon::parse($accountingDate) : null,
                'paid_at' => $order->paid_at ? Carbon::parse($order->paid_at) : null,
                'amount' => round($amount, 5),
                'reference' => '#' . $order->id,
                'is_paid' => $order->is_paid,
            ];
            $total += round($amount, 5);
        }

        // Consignment cost from sales
        $saleIds = DB::table('sales')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('id');

        if ($saleIds->isNotEmpty()) {
            $consignmentSuppliers = DB::table('suppliers')
                ->where('type', 'consignment')
                ->where('is_active', 1)
                ->get(['id', 'name']);

            foreach ($consignmentSuppliers as $supplier) {
                $productIds = DB::table('product_supplier')
                    ->where('supplier_id', $supplier->id)
                    ->pluck('product_id');

                if ($productIds->isEmpty()) continue;

                $items = DB::table('sale_items')
                    ->whereIn('sale_id', $saleIds)
                    ->whereIn('product_id', $productIds)
                    ->whereNull('exchanged_at')
                    ->get(['product_id', 'quantity']);

                $cost = 0;
                foreach ($items as $item) {
                    $purchasePrice = (float) DB::table('product_supplier')
                        ->where('product_id', $item->product_id)
                        ->where('supplier_id', $supplier->id)
                        ->value('purchase_price');
                    $cost += $purchasePrice * $item->quantity;
                }

                if ($cost > 0) {
                    $entries[] = [
                        'type' => 'consignment',
                        'supplier_name' => $supplier->name,
                        'invoice_date' => null,
                        'paid_at' => null,
                        'amount' => round($cost, 5),
                        'reference' => '',
                        'is_paid' => false,
                    ];
                    $total += round($cost, 5);
                }
            }
        }

        // Sort: buyer first, then consignment by amount desc
        usort($entries, function ($a, $b) {
            if ($a['type'] !== $b['type']) return $a['type'] === 'buyer' ? -1 : 1;
            return $b['amount'] <=> $a['amount'];
        });

        return [
            'entries' => collect($entries),
            'total' => round($total, 5),
        ];
    }
}
