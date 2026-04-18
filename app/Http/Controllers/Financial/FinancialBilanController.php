<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\GeneralInvoice;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialBilanController extends Controller
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
            $data = $this->generateBilan($store, $month, $exchangeRate);

            return view('financial.bilan', array_merge(compact('stores', 'store', 'month', 'exchangeRate'), $data));
        }

        return view('financial.bilan', compact('stores', 'store', 'month', 'exchangeRate'));
    }

    private function generateBilan(Store $store, string $month, int $exchangeRate): array
    {
        $year = (int) substr($month, 0, 4);
        $m = (int) substr($month, 5, 2);
        $startDate = Carbon::create($year, $m, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        $lastDay = $endDate->format('d');
        $monthLabel = $startDate->format('F-Y');

        // 5. MONTHLY EXPENSE TO SUPPLIER (calculated first, used by income statement)
        $supplierExpenseReport = $this->buildSupplierExpenseReport($store, $startDate, $endDate);

        // 1. INCOME STATEMENT
        $incomeStatement = $this->buildIncomeStatement($store, $year, $m, $startDate, $endDate, $exchangeRate, $supplierExpenseReport['total']);

        // 2. EXPENSE BOOK DAILY
        $expenseBook = $this->buildExpenseBook($store, $year, $m, $exchangeRate);

        // 3. STAFF PAYROLL REPORT
        $payrollReport = $this->buildPayrollReport($store, $month);

        // 4. MONTHLY SALES REPORT
        $salesReport = $this->buildSalesReport($store, $startDate, $endDate);

        return compact(
            'monthLabel', 'startDate', 'endDate', 'lastDay',
            'incomeStatement', 'expenseBook', 'payrollReport',
            'salesReport', 'supplierExpenseReport'
        );
    }

    private function buildIncomeStatement(Store $store, int $year, int $m, Carbon $startDate, Carbon $endDate, int $exchangeRate, float $supplierExpenseTotal): array
    {
        // Revenue: net of credits minus debits on Shop Sales account (701)
        // Credits = POS sales, Debits = exchanges/appros (internal stock movements)
        $saleRevenueCredits = FinancialTransaction::where('store_id', $store->id)
            ->where('account_id', FinancialAccount::idByCode('701'))
            ->where('direction', 'credit')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $m)
            ->sum('amount');

        $saleRevenueDebits = FinancialTransaction::where('store_id', $store->id)
            ->where('account_id', FinancialAccount::idByCode('701'))
            ->where('direction', 'debit')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $m)
            ->sum('amount');

        // The income statement shows only the net revenue (credits - debits on account 701)
        $saleRevenue = $saleRevenueCredits;
        $revenueDeductions = 0;
        $netRevenue = $saleRevenue;

        // Expenses from general_invoices (grouped by account)
        $expensesByAccount = GeneralInvoice::where('store_id', $store->id)
            ->where('status', 'paid')
            ->where(function ($q) use ($year, $m) {
                $q->whereYear('due_date', $year)->whereMonth('due_date', $m);
            })
            ->selectRaw('account_id, SUM(amount) as total')
            ->groupBy('account_id')
            ->pluck('total', 'account_id');

        // Convert KHR amounts (general_invoices stores amounts in USD already based on the data)
        $salaryExpense = $this->getSalaryExpenseForBilan($store, sprintf('%04d-%02d', $year, $m));
        $rentalExpense = $expensesByAccount[FinancialAccount::idByCode('50001')] ?? 0;
        $utilityExpense = $expensesByAccount[FinancialAccount::idByCode('50002')] ?? 0;
        $supplyExpense = $expensesByAccount[FinancialAccount::idByCode('50004')] ?? 0;
        $otherExpense = $expensesByAccount[FinancialAccount::idByCode('50005')] ?? 0;
        $taxExpense = $expensesByAccount[FinancialAccount::idByCode('50006')] ?? 0;
        $equipmentExpense = $expensesByAccount[FinancialAccount::idByCode('50007')] ?? 0;

        // Supplier expense: consignment cost + buyer supplier orders paid
        $supplierExpense = $supplierExpenseTotal;

        $totalExpense = $salaryExpense + $supplierExpense + $utilityExpense + $supplyExpense
            + $rentalExpense + $otherExpense + $taxExpense + $equipmentExpense;

        $netProfitLoss = $netRevenue - $totalExpense;

        return compact(
            'saleRevenue', 'revenueDeductions', 'netRevenue',
            'salaryExpense', 'supplierExpense', 'utilityExpense',
            'supplyExpense', 'rentalExpense', 'otherExpense',
            'taxExpense', 'equipmentExpense', 'totalExpense', 'netProfitLoss'
        );
    }

    private function getSalaryExpenseForBilan(Store $store, string $period): float
    {
        return (float) DB::table('salary_payments')
            ->where('store_id', $store->id)
            ->where('period', $period)
            ->sum('net_amount');
    }

    private function buildExpenseBook(Store $store, int $year, int $m, int $exchangeRate): array
    {
        $invoices = GeneralInvoice::where('store_id', $store->id)
            ->where('status', 'paid')
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $m)
            ->with('account', 'category')
            ->orderBy('due_date')
            ->get();

        $totalUsd = 0;
        $totalKhr = 0;
        $entries = [];

        foreach ($invoices as $invoice) {
            $amountUsd = $invoice->amount;
            $amountKhr = 0;

            // If amount is in KHR (stored as small fractional USD), detect by checking if category/label hints at KHR
            // The general_invoices table stores amounts in USD already
            $totalUsd += $amountUsd;

            $entries[] = [
                'date' => Carbon::parse($invoice->due_date),
                'invoice_no' => $invoice->category?->name ?? 'N/A',
                'vendor' => $invoice->category?->name ?? 'General',
                'description' => $invoice->label,
                'quantity' => null,
                'unit_price' => null,
                'total_khr' => $amountKhr,
                'total_usd' => $amountUsd,
                'account_type' => $invoice->account?->name ?? '-',
                'note' => $invoice->note,
            ];
        }

        $grandTotal = $totalUsd + ($totalKhr / $exchangeRate);

        return compact('entries', 'totalUsd', 'totalKhr', 'grandTotal');
    }

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

            $employees[] = [
                'name' => $staff->name ?? 'Unknown',
                'sex' => '-',
                'position' => '-',
                'status' => $staff->contract_status ?? '-',
                'day_off' => 4, // Standard
                'base_salary' => $payment->base_salary,
                'overtime' => $payment->overtime_amount,
                'gasoline' => 0,
                'cm_reseller' => $payment->commission_amount,
                'staff_borrow' => $payment->advances_deduction,
                'deduction_leave' => $payment->absence_deduction,
                'deduction_other' => $payment->penalty_amount,
                'total_deduct' => $totalDeduct,
                'total_amount' => $payment->net_amount,
            ];

            $totalAmount += $payment->net_amount;
        }

        return compact('employees', 'totalAmount');
    }

    private function buildSalesReport(Store $store, Carbon $startDate, Carbon $endDate): array
    {
        // Get all sales for store in period
        $saleIds = DB::table('sales')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('id');

        if ($saleIds->isEmpty()) {
            return ['entries' => collect(), 'totalAba' => 0, 'totalCash' => 0, 'grandTotal' => 0];
        }

        // Get sale items grouped by brand, with payment method split
        $salesWithPayment = DB::table('sales')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['id', 'payment_type']);

        $salePaymentMap = $salesWithPayment->pluck('payment_type', 'id');

        // Get items with brand info
        $items = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->whereIn('sale_items.sale_id', $saleIds)
            ->whereNull('sale_items.exchanged_at')
            ->select(
                'sale_items.sale_id',
                'brands.name as brand_name',
                DB::raw('sale_items.price * sale_items.quantity as line_total')
            )
            ->get();

        // Group by brand with ABA/Cash split
        $brandTotals = [];
        foreach ($items as $item) {
            $brand = $item->brand_name;
            if (!isset($brandTotals[$brand])) {
                $brandTotals[$brand] = ['aba' => 0, 'cash' => 0, 'total' => 0];
            }

            $paymentType = $salePaymentMap[$item->sale_id] ?? '';
            $isAba = $this->isAbcPayment($paymentType);

            if ($isAba) {
                $brandTotals[$brand]['aba'] += $item->line_total;
            } else {
                $brandTotals[$brand]['cash'] += $item->line_total;
            }
            $brandTotals[$brand]['total'] += $item->line_total;
        }

        // Also include gift card / gift box / custom service items (without product)
        $otherItems = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->whereNull('exchanged_at')
            ->whereNull('product_id')
            ->get(['sale_id', 'price', 'quantity', 'item_type']);

        foreach ($otherItems as $item) {
            $brand = ucfirst($item->item_type ?? 'Other');
            if (!isset($brandTotals[$brand])) {
                $brandTotals[$brand] = ['aba' => 0, 'cash' => 0, 'total' => 0];
            }
            $lineTotal = $item->price * $item->quantity;
            $paymentType = $salePaymentMap[$item->sale_id] ?? '';
            if ($this->isAbcPayment($paymentType)) {
                $brandTotals[$brand]['aba'] += $lineTotal;
            } else {
                $brandTotals[$brand]['cash'] += $lineTotal;
            }
            $brandTotals[$brand]['total'] += $lineTotal;
        }

        // Sort by total descending
        arsort($brandTotals);

        $totalAba = array_sum(array_column($brandTotals, 'aba'));
        $totalCash = array_sum(array_column($brandTotals, 'cash'));
        $grandTotal = array_sum(array_column($brandTotals, 'total'));

        return [
            'entries' => collect($brandTotals),
            'totalAba' => $totalAba,
            'totalCash' => $totalCash,
            'grandTotal' => $grandTotal,
        ];
    }

    private function buildSupplierExpenseReport(Store $store, Carbon $startDate, Carbon $endDate): array
    {
        $year = $startDate->year;
        $m = $startDate->month;

        // Get all sales for store in period
        $saleIds = DB::table('sales')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('id');

        $entries = [];
        $total = 0;

        // 1. Buyer supplier payments from financial transactions (commande fournisseur)
        $buyerTransactions = FinancialTransaction::where('store_id', $store->id)
            ->where('account_id', FinancialAccount::idByCode('401'))
            ->where('direction', 'debit')
            ->where('label', 'LIKE', '%commande fournisseur%')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $m)
            ->orderBy('transaction_date')
            ->get();

        foreach ($buyerTransactions as $tx) {
            // Extract supplier name from label: "Paiement commande fournisseur : SUPPLIER NAME"
            $supplierName = trim(str_replace('Paiement commande fournisseur :', '', $tx->label));

            $entries[] = [
                'invoice_date' => Carbon::parse($tx->transaction_date),
                'invoice_no' => $tx->external_reference ?? '',
                'supplier_name' => $supplierName,
                'amount' => (float) $tx->amount,
                'paid_amount' => 0,
                'remaining' => 0,
                'date_paid' => null,
                'note' => '',
            ];
            $total += (float) $tx->amount;
        }

        // 2. Consignment supplier cost based on sales
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
                    $entries['consignment_' . $supplier->id] = [
                        'invoice_date' => null,
                        'invoice_no' => '',
                        'supplier_name' => $supplier->name,
                        'amount' => round($cost, 5),
                        'paid_amount' => 0,
                        'remaining' => 0,
                        'date_paid' => null,
                        'note' => '',
                    ];
                    $total += round($cost, 5);
                }
            }
        }

        // Sort: buyer orders first, then consignment by amount desc
        uasort($entries, function ($a, $b) {
            // Buyer orders (with invoice_date) first
            $aHasDate = $a['invoice_date'] !== null;
            $bHasDate = $b['invoice_date'] !== null;
            if ($aHasDate !== $bHasDate) return $bHasDate ? 1 : -1;
            return $b['amount'] <=> $a['amount'];
        });

        return [
            'entries' => collect($entries)->values(),
            'total' => round($total, 5),
        ];
    }

    private function isAbcPayment(string $paymentType): bool
    {
        if (empty($paymentType)) return false;

        $type = strtoupper(trim($paymentType));

        return in_array($type, ['VISA CARD', 'ABA KHQR', 'ABA', 'KHQR', 'VISA', 'CARD', 'BANK_TRANSFER']);
    }
}
