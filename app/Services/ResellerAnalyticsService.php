<?php

namespace App\Services;

use App\Models\Reseller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResellerAnalyticsService
{
    public function __construct(private Reseller $reseller) {}

    public function headline(): array
    {
        $r = $this->reseller;
        $isConsignment = $r->type === 'consignment';

        // Deliveries aggregate
        $deliv = DB::table('reseller_stock_deliveries as d')
            ->leftJoin('reseller_stock_delivery_product as dp', 'dp.reseller_stock_delivery_id', '=', 'd.id')
            ->where('d.reseller_id', $r->id)
            ->whereIn('d.status', ['ready_to_ship', 'shipped'])
            ->selectRaw('COUNT(DISTINCT d.id) as cnt, SUM(dp.quantity) as q, SUM(dp.quantity * dp.unit_price) as val')
            ->first();

        // Sales reports aggregate (consignment only)
        $reports = null; $unitsSold = 0; $revenueReported = 0.0;
        if ($isConsignment) {
            $reports = DB::table('reseller_sales_reports as sr')
                ->leftJoin('reseller_sales_report_items as sri', 'sri.report_id', '=', 'sr.id')
                ->where('sr.reseller_id', $r->id)
                ->selectRaw('COUNT(DISTINCT sr.id) as cnt, SUM(sri.quantity_sold) as q, SUM(sri.quantity_sold * sri.unit_price) as rev')
                ->first();
            $unitsSold = (int) ($reports->q ?? 0);
            $revenueReported = (float) ($reports->rev ?? 0);
        }

        // Invoices: use total_amount and subtract sum(payments) for outstanding
        $invoiced = (float) DB::table('resellers_invoices')->where('reseller_id', $r->id)->sum('total_amount');
        $paid = (float) DB::table('resellers_invoices as i')
            ->join('resellers_invoice_payments as p', 'p.resellers_invoice_id', '=', 'i.id')
            ->where('i.reseller_id', $r->id)
            ->sum('p.amount');
        $outstanding = max(0.0, $invoiced - $paid);
        $outstandingCount = (int) DB::table('resellers_invoices')
            ->where('reseller_id', $r->id)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->count();

        // Returns
        $returns = DB::table('reseller_stock_returns as sr')
            ->leftJoin('reseller_stock_return_items as sri', 'sri.reseller_stock_return_id', '=', 'sr.id')
            ->where('sr.reseller_id', $r->id)
            ->where('sr.status', 'validated')
            ->selectRaw('COUNT(DISTINCT sr.id) as cnt, SUM(sri.quantity) as q')
            ->first();

        // Revenue (to us): for consignment = sum of invoices (paid or not). For buyer = delivered value (what they owe us).
        $revenueTotal = $isConsignment ? $invoiced : (float) ($deliv->val ?? 0);
        $unitsFlow = $isConsignment ? $unitsSold : (int) ($deliv->q ?? 0);

        // Margin via supplier costs
        $costs = $this->productCosts();
        $cogs = $this->cogsFromChannel($isConsignment, $costs);
        $marginTotal = $revenueTotal - $cogs;
        $marginPct = $revenueTotal > 0 ? round(($marginTotal / $revenueTotal) * 100, 2) : null;

        $sellThrough = null;
        if ($isConsignment && (int) ($deliv->q ?? 0) > 0) {
            $sellThrough = round(($unitsSold / (int) $deliv->q) * 100, 1);
        }

        return [
            'is_consignment' => $isConsignment,
            'deliveries_count' => (int) ($deliv->cnt ?? 0),
            'delivered_value' => round((float) ($deliv->val ?? 0), 2),
            'units_delivered' => (int) ($deliv->q ?? 0),
            'reports_count' => (int) ($reports->cnt ?? 0),
            'revenue_reported' => round($revenueReported, 2),
            'units_sold' => $unitsSold,
            'sell_through_pct' => $sellThrough,
            'invoiced_total' => round($invoiced, 2),
            'paid_total' => round($paid, 2),
            'outstanding' => round($outstanding, 2),
            'outstanding_count' => $outstandingCount,
            'returns_count' => (int) ($returns->cnt ?? 0),
            'units_returned' => (int) ($returns->q ?? 0),
            'revenue_total' => round($revenueTotal, 2),
            'units_flow' => $unitsFlow,
            'margin_total' => round($marginTotal, 2),
            'margin_pct' => $marginPct,
        ];
    }

    /** @return array<int,array{month:string,deliveries:int,delivered_value:float,reports:int,revenue:float}> */
    public function byMonth(int $months = 12): array
    {
        $r = $this->reseller;
        $since = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $delivRows = DB::table('reseller_stock_deliveries as d')
            ->leftJoin('reseller_stock_delivery_product as dp', 'dp.reseller_stock_delivery_id', '=', 'd.id')
            ->where('d.reseller_id', $r->id)
            ->where('d.created_at', '>=', $since)
            ->whereIn('d.status', ['ready_to_ship', 'shipped'])
            ->selectRaw("DATE_FORMAT(d.created_at, '%Y-%m') as month, COUNT(DISTINCT d.id) as cnt, SUM(dp.quantity * dp.unit_price) as val")
            ->groupBy('month')->get()->keyBy('month');

        $reportRows = collect();
        if ($r->type === 'consignment') {
            $reportRows = DB::table('reseller_sales_reports as sr')
                ->leftJoin('reseller_sales_report_items as sri', 'sri.report_id', '=', 'sr.id')
                ->where('sr.reseller_id', $r->id)
                ->where('sr.end_date', '>=', $since)
                ->selectRaw("DATE_FORMAT(sr.end_date, '%Y-%m') as month, COUNT(DISTINCT sr.id) as cnt, SUM(sri.quantity_sold * sri.unit_price) as rev")
                ->groupBy('month')->get()->keyBy('month');
        }

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $since->copy()->addMonths($i)->format('Y-m');
            $series[] = [
                'month' => $m,
                'deliveries' => (int) ($delivRows[$m]->cnt ?? 0),
                'delivered_value' => round((float) ($delivRows[$m]->val ?? 0), 2),
                'reports' => (int) ($reportRows[$m]->cnt ?? 0),
                'revenue' => round((float) ($reportRows[$m]->rev ?? 0), 2),
            ];
        }
        return $series;
    }

    /** @return array<int,array{year:string,deliveries:int,reports:int,delivered_value:float,revenue:float,margin:float,margin_pct:?float}> */
    public function byYear(): array
    {
        $r = $this->reseller;
        $isConsignment = $r->type === 'consignment';
        $costs = $this->productCosts();

        $deliv = DB::table('reseller_stock_deliveries as d')
            ->leftJoin('reseller_stock_delivery_product as dp', 'dp.reseller_stock_delivery_id', '=', 'd.id')
            ->where('d.reseller_id', $r->id)
            ->whereIn('d.status', ['ready_to_ship', 'shipped'])
            ->selectRaw("YEAR(d.created_at) as y, COUNT(DISTINCT d.id) as cnt, SUM(dp.quantity * dp.unit_price) as val, SUM(dp.quantity) as q")
            ->groupBy('y')->get()->keyBy('y');

        $reports = collect();
        $reportProductAgg = []; // year => product_id => qty, revenue
        if ($isConsignment) {
            $reports = DB::table('reseller_sales_reports as sr')
                ->leftJoin('reseller_sales_report_items as sri', 'sri.report_id', '=', 'sr.id')
                ->where('sr.reseller_id', $r->id)
                ->selectRaw("YEAR(sr.end_date) as y, COUNT(DISTINCT sr.id) as cnt, SUM(sri.quantity_sold * sri.unit_price) as rev")
                ->groupBy('y')->get()->keyBy('y');

            $rows = DB::table('reseller_sales_reports as sr')
                ->join('reseller_sales_report_items as sri', 'sri.report_id', '=', 'sr.id')
                ->where('sr.reseller_id', $r->id)
                ->selectRaw("YEAR(sr.end_date) as y, sri.product_id, SUM(sri.quantity_sold) as q, SUM(sri.quantity_sold * sri.unit_price) as r")
                ->groupBy('y', 'sri.product_id')->get();
            foreach ($rows as $row) {
                $reportProductAgg[$row->y][$row->product_id] = ['q' => (int) $row->q, 'r' => (float) $row->r];
            }
        } else {
            // For buyer: cogs from delivery pivot
            $rows = DB::table('reseller_stock_deliveries as d')
                ->join('reseller_stock_delivery_product as dp', 'dp.reseller_stock_delivery_id', '=', 'd.id')
                ->where('d.reseller_id', $r->id)
                ->whereIn('d.status', ['ready_to_ship', 'shipped'])
                ->selectRaw("YEAR(d.created_at) as y, dp.product_id, SUM(dp.quantity) as q, SUM(dp.quantity * dp.unit_price) as r")
                ->groupBy('y', 'dp.product_id')->get();
            foreach ($rows as $row) {
                $reportProductAgg[$row->y][$row->product_id] = ['q' => (int) $row->q, 'r' => (float) $row->r];
            }
        }

        $years = array_unique(array_merge($deliv->keys()->all(), $reports->keys()->all(), array_keys($reportProductAgg)));
        rsort($years);
        $out = [];
        foreach ($years as $y) {
            $revenue = 0.0; $cogs = 0.0;
            foreach ($reportProductAgg[$y] ?? [] as $pid => $e) {
                $revenue += $e['r'];
                $cogs += $e['q'] * ($costs[$pid] ?? 0);
            }
            $margin = $revenue - $cogs;
            $out[] = [
                'year' => (string) $y,
                'deliveries' => (int) ($deliv[$y]->cnt ?? 0),
                'reports' => (int) ($reports[$y]->cnt ?? 0),
                'delivered_value' => round((float) ($deliv[$y]->val ?? 0), 2),
                'revenue' => round($revenue, 2),
                'margin' => round($margin, 2),
                'margin_pct' => $revenue > 0 ? round(($margin / $revenue) * 100, 1) : null,
            ];
        }
        return $out;
    }

    public function topProducts(int $limit = 10): array
    {
        $r = $this->reseller;
        $isConsignment = $r->type === 'consignment';
        $costs = $this->productCosts();

        if ($isConsignment) {
            $rows = DB::table('reseller_sales_report_items as sri')
                ->join('reseller_sales_reports as sr', 'sr.id', '=', 'sri.report_id')
                ->where('sr.reseller_id', $r->id)
                ->selectRaw('sri.product_id, SUM(sri.quantity_sold) as q, SUM(sri.quantity_sold * sri.unit_price) as r')
                ->groupBy('sri.product_id')->get();
        } else {
            $rows = DB::table('reseller_stock_delivery_product as dp')
                ->join('reseller_stock_deliveries as d', 'd.id', '=', 'dp.reseller_stock_delivery_id')
                ->where('d.reseller_id', $r->id)
                ->whereIn('d.status', ['ready_to_ship', 'shipped'])
                ->selectRaw('dp.product_id, SUM(dp.quantity) as q, SUM(dp.quantity * dp.unit_price) as r')
                ->groupBy('dp.product_id')->get();
        }

        $list = [];
        foreach ($rows as $row) {
            $cogs = (int) $row->q * ($costs[$row->product_id] ?? 0);
            $margin = (float) $row->r - $cogs;
            $list[] = [
                'product_id' => (int) $row->product_id,
                'units' => (int) $row->q,
                'revenue' => round((float) $row->r, 2),
                'margin' => round($margin, 2),
                'margin_pct' => $row->r > 0 ? round(($margin / $row->r) * 100, 1) : null,
            ];
        }
        usort($list, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        $list = array_slice($list, 0, $limit);

        $names = DB::table('products')->whereIn('id', array_column($list, 'product_id'))->pluck('name', 'id');
        foreach ($list as &$e) {
            $raw = $names[$e['product_id']] ?? '';
            $arr = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
            $e['name'] = $arr['en'] ?? $arr['fr'] ?? ('Product #' . $e['product_id']);
        }
        return $list;
    }

    // --- helpers ---

    /** @return array<int,float> product_id => min supplier purchase_price */
    private function productCosts(): array
    {
        // Join products actually linked to this reseller to minimise scope
        $productIds = $this->resellerProductIds();
        if (empty($productIds)) return [];

        return DB::table('product_supplier')
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, MIN(purchase_price) as cost')
            ->groupBy('product_id')
            ->pluck('cost', 'product_id')
            ->map(fn($v) => (float) $v)
            ->all();
    }

    private function resellerProductIds(): array
    {
        $r = $this->reseller;
        $fromDeliv = DB::table('reseller_stock_delivery_product as dp')
            ->join('reseller_stock_deliveries as d', 'd.id', '=', 'dp.reseller_stock_delivery_id')
            ->where('d.reseller_id', $r->id)
            ->pluck('dp.product_id');

        $fromReports = collect();
        if ($r->type === 'consignment') {
            $fromReports = DB::table('reseller_sales_report_items as sri')
                ->join('reseller_sales_reports as sr', 'sr.id', '=', 'sri.report_id')
                ->where('sr.reseller_id', $r->id)
                ->pluck('sri.product_id');
        }
        return $fromDeliv->concat($fromReports)->unique()->values()->all();
    }

    private function cogsFromChannel(bool $isConsignment, array $costs): float
    {
        $r = $this->reseller;
        if ($isConsignment) {
            $rows = DB::table('reseller_sales_report_items as sri')
                ->join('reseller_sales_reports as sr', 'sr.id', '=', 'sri.report_id')
                ->where('sr.reseller_id', $r->id)
                ->selectRaw('sri.product_id, SUM(sri.quantity_sold) as q')
                ->groupBy('sri.product_id')->get();
        } else {
            $rows = DB::table('reseller_stock_delivery_product as dp')
                ->join('reseller_stock_deliveries as d', 'd.id', '=', 'dp.reseller_stock_delivery_id')
                ->where('d.reseller_id', $r->id)
                ->whereIn('d.status', ['ready_to_ship', 'shipped'])
                ->selectRaw('dp.product_id, SUM(dp.quantity) as q')
                ->groupBy('dp.product_id')->get();
        }
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (int) $row->q * ($costs[$row->product_id] ?? 0);
        }
        return $total;
    }
}
