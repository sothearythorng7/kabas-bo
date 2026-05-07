<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupplierAnalyticsService
{
    public function __construct(private Supplier $supplier) {}

    /**
     * @return array{orders_total:int, spent_total:float, revenue_total:float, margin_total:float, margin_pct:?float, units_bought:int, units_sold:int, unpaid_total:float, unpaid_count:int, avg_lead_time_days:?float, fill_rate_pct:?float}
     */
    public function headline(): array
    {
        $s = $this->supplier;

        $ordersTotal = DB::table('supplier_orders')->where('supplier_id', $s->id)->count();

        // Spent: invoiced amount on received orders (products + raw materials)
        $spentProd = (float) DB::table('supplier_orders as so')
            ->join('supplier_order_product as sop', 'sop.supplier_order_id', '=', 'so.id')
            ->where('so.supplier_id', $s->id)->where('so.status', 'received')
            ->sum(DB::raw('sop.quantity_received * COALESCE(sop.invoice_price, sop.purchase_price)'));

        $spentRaw = 0.0;
        if (DB::getSchemaBuilder()->hasTable('supplier_order_raw_material')) {
            $spentRaw = (float) DB::table('supplier_orders as so')
                ->join('supplier_order_raw_material as sorm', 'sorm.supplier_order_id', '=', 'so.id')
                ->where('so.supplier_id', $s->id)->where('so.status', 'received')
                ->sum(DB::raw('sorm.quantity_received * COALESCE(sorm.invoice_price, sorm.purchase_price)'));
        }
        $spentTotal = $spentProd + $spentRaw;

        $unitsBought = (int) DB::table('supplier_orders as so')
            ->join('supplier_order_product as sop', 'sop.supplier_order_id', '=', 'so.id')
            ->where('so.supplier_id', $s->id)->where('so.status', 'received')
            ->sum('sop.quantity_received');

        $unpaid = DB::table('supplier_orders as so')
            ->join('supplier_order_product as sop', 'sop.supplier_order_id', '=', 'so.id')
            ->where('so.supplier_id', $s->id)->where('so.status', 'received')->where('so.is_paid', false)
            ->selectRaw('COUNT(DISTINCT so.id) as cnt, SUM(sop.quantity_received * COALESCE(sop.invoice_price, sop.purchase_price)) as amt')
            ->first();

        $salesAgg = $this->salesAggregate();
        $revenueTotal = (float) ($salesAgg['revenue'] ?? 0);
        $unitsSold = (int) ($salesAgg['units'] ?? 0);
        $cogs = (float) ($salesAgg['cogs'] ?? 0);
        $marginTotal = $revenueTotal - $cogs;
        $marginPct = $revenueTotal > 0 ? round(($marginTotal / $revenueTotal) * 100, 2) : null;

        // Fill rate & lead time
        $fill = DB::table('supplier_orders as so')
            ->join('supplier_order_product as sop', 'sop.supplier_order_id', '=', 'so.id')
            ->where('so.supplier_id', $s->id)->where('so.status', 'received')
            ->selectRaw('SUM(sop.quantity_received) as r, SUM(sop.quantity_ordered) as o')
            ->first();
        $fillRate = ($fill && $fill->o > 0) ? round(($fill->r / $fill->o) * 100, 1) : null;

        $leadTime = DB::table('supplier_orders')
            ->where('supplier_id', $s->id)->where('status', 'received')
            ->whereNotNull('invoice_date')
            ->selectRaw('AVG(DATEDIFF(invoice_date, created_at)) as d')
            ->value('d');

        return [
            'orders_total' => $ordersTotal,
            'spent_total' => round($spentTotal, 2),
            'revenue_total' => round($revenueTotal, 2),
            'margin_total' => round($marginTotal, 2),
            'margin_pct' => $marginPct,
            'units_bought' => $unitsBought,
            'units_sold' => $unitsSold,
            'unpaid_total' => round((float) ($unpaid->amt ?? 0), 2),
            'unpaid_count' => (int) ($unpaid->cnt ?? 0),
            'avg_lead_time_days' => $leadTime ? round((float) $leadTime, 1) : null,
            'fill_rate_pct' => $fillRate,
        ];
    }

    /** @return array<int,array{month:string,count:int,spent:float}> */
    public function ordersByMonth(int $months = 12): array
    {
        $since = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $rows = DB::table('supplier_orders as so')
            ->leftJoin('supplier_order_product as sop', 'sop.supplier_order_id', '=', 'so.id')
            ->where('so.supplier_id', $this->supplier->id)
            ->where('so.created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(so.created_at, '%Y-%m') as month")
            ->selectRaw('COUNT(DISTINCT so.id) as count')
            ->selectRaw("SUM(CASE WHEN so.status = 'received' THEN sop.quantity_received * COALESCE(sop.invoice_price, sop.purchase_price) ELSE 0 END) as spent")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill gaps with zeros for months without orders
        $map = [];
        foreach ($rows as $r) {
            $map[$r->month] = ['count' => (int) $r->count, 'spent' => (float) $r->spent];
        }
        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $since->copy()->addMonths($i)->format('Y-m');
            $series[] = [
                'month' => $m,
                'count' => $map[$m]['count'] ?? 0,
                'spent' => round($map[$m]['spent'] ?? 0, 2),
            ];
        }
        return $series;
    }

    /** @return array<int,array{year:string,orders:int,spent:float,revenue:float,margin:float,margin_pct:?float}> */
    public function byYear(): array
    {
        $supplierProductIds = $this->supplierProductIds();

        $buy = DB::table('supplier_orders as so')
            ->join('supplier_order_product as sop', 'sop.supplier_order_id', '=', 'so.id')
            ->where('so.supplier_id', $this->supplier->id)->where('so.status', 'received')
            ->selectRaw("YEAR(so.created_at) as y")
            ->selectRaw('COUNT(DISTINCT so.id) as orders')
            ->selectRaw('SUM(sop.quantity_received * COALESCE(sop.invoice_price, sop.purchase_price)) as spent')
            ->groupBy('y')
            ->get()
            ->keyBy('y');

        // Revenue + cogs per year across channels
        $byYear = [];
        if (!empty($supplierProductIds)) {
            $posRows = DB::table('sale_items as si')
                ->join('sales as s', 's.id', '=', 'si.sale_id')
                ->whereIn('si.product_id', $supplierProductIds)
                ->where('si.item_type', 'product')
                ->selectRaw("YEAR(s.created_at) as y, si.product_id, SUM(si.quantity) as q, SUM(si.quantity * si.price) as r")
                ->groupBy('y', 'si.product_id')->get();
            foreach ($posRows as $r) $this->addToYear($byYear, $r->y, $r->product_id, $r->q, $r->r);

            $webRows = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->whereIn('oi.product_id', $supplierProductIds)
                ->where('oi.item_type', 'product')
                ->where('o.payment_status', 'paid')
                ->selectRaw("YEAR(COALESCE(o.paid_at, o.created_at)) as y, oi.product_id, SUM(oi.quantity) as q, SUM(oi.quantity * oi.unit_price) as r")
                ->groupBy('y', 'oi.product_id')->get();
            foreach ($webRows as $r) $this->addToYear($byYear, $r->y, $r->product_id, $r->q, $r->r);

            $resRows = DB::table('reseller_sales_report_items as rsi')
                ->join('reseller_sales_reports as rs', 'rs.id', '=', 'rsi.report_id')
                ->whereIn('rsi.product_id', $supplierProductIds)
                ->selectRaw("YEAR(rs.end_date) as y, rsi.product_id, SUM(rsi.quantity_sold) as q, SUM(rsi.quantity_sold * rsi.unit_price) as r")
                ->groupBy('y', 'rsi.product_id')->get();
            foreach ($resRows as $r) $this->addToYear($byYear, $r->y, $r->product_id, $r->q, $r->r);
        }

        $costs = $this->productCosts();
        $years = array_unique(array_merge(array_keys($byYear), $buy->keys()->all()));
        rsort($years);
        $out = [];
        foreach ($years as $y) {
            $revenue = 0.0; $cogs = 0.0;
            foreach (($byYear[$y] ?? []) as $pid => $entry) {
                $revenue += $entry['r'];
                $cogs += $entry['q'] * ($costs[$pid] ?? 0);
            }
            $margin = $revenue - $cogs;
            $out[] = [
                'year' => (string) $y,
                'orders' => (int) ($buy[$y]->orders ?? 0),
                'spent' => round((float) ($buy[$y]->spent ?? 0), 2),
                'revenue' => round($revenue, 2),
                'margin' => round($margin, 2),
                'margin_pct' => $revenue > 0 ? round(($margin / $revenue) * 100, 1) : null,
            ];
        }
        return $out;
    }

    /** @return array<int,array{product_id:int,name:string,units:int,revenue:float,margin:float,margin_pct:?float}> */
    public function topProducts(int $limit = 10): array
    {
        $supplierProductIds = $this->supplierProductIds();
        if (empty($supplierProductIds)) return [];

        $agg = []; // product_id => [q, r]
        $add = function ($rows) use (&$agg) {
            foreach ($rows as $r) {
                if (!isset($agg[$r->product_id])) $agg[$r->product_id] = ['q' => 0, 'r' => 0.0];
                $agg[$r->product_id]['q'] += (int) $r->q;
                $agg[$r->product_id]['r'] += (float) $r->r;
            }
        };

        $add(DB::table('sale_items')
            ->whereIn('product_id', $supplierProductIds)
            ->where('item_type', 'product')
            ->selectRaw('product_id, SUM(quantity) as q, SUM(quantity * price) as r')
            ->groupBy('product_id')->get());

        $add(DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereIn('oi.product_id', $supplierProductIds)
            ->where('oi.item_type', 'product')
            ->where('o.payment_status', 'paid')
            ->selectRaw('oi.product_id as product_id, SUM(oi.quantity) as q, SUM(oi.quantity * oi.unit_price) as r')
            ->groupBy('oi.product_id')->get());

        $add(DB::table('reseller_sales_report_items')
            ->whereIn('product_id', $supplierProductIds)
            ->selectRaw('product_id, SUM(quantity_sold) as q, SUM(quantity_sold * unit_price) as r')
            ->groupBy('product_id')->get());

        $costs = $this->productCosts();

        $rows = [];
        foreach ($agg as $pid => $v) {
            $cogs = $v['q'] * ($costs[$pid] ?? 0);
            $margin = $v['r'] - $cogs;
            $rows[] = [
                'product_id' => $pid,
                'units' => $v['q'],
                'revenue' => round($v['r'], 2),
                'margin' => round($margin, 2),
                'margin_pct' => $v['r'] > 0 ? round(($margin / $v['r']) * 100, 1) : null,
            ];
        }
        usort($rows, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        $rows = array_slice($rows, 0, $limit);

        // Attach names
        $names = DB::table('products')->whereIn('id', array_column($rows, 'product_id'))->pluck('name', 'id');
        foreach ($rows as &$r) {
            $raw = $names[$r['product_id']] ?? '';
            $arr = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
            $r['name'] = $arr['en'] ?? $arr['fr'] ?? ('Product #' . $r['product_id']);
        }
        return $rows;
    }

    /** @return array{count:int,items:int,value:float} */
    public function returnsStats(): array
    {
        if (!DB::getSchemaBuilder()->hasTable('supplier_returns')) {
            return ['count' => 0, 'items' => 0, 'value' => 0.0];
        }
        $agg = DB::table('supplier_returns as sr')
            ->leftJoin('supplier_return_items as sri', 'sri.supplier_return_id', '=', 'sr.id')
            ->where('sr.supplier_id', $this->supplier->id)
            ->selectRaw('COUNT(DISTINCT sr.id) as cnt, SUM(sri.quantity) as items, SUM(sri.quantity * COALESCE(sri.unit_price, 0)) as value')
            ->first();
        return [
            'count' => (int) ($agg->cnt ?? 0),
            'items' => (int) ($agg->items ?? 0),
            'value' => round((float) ($agg->value ?? 0), 2),
        ];
    }

    // ---------- helpers ----------

    private function supplierProductIds(): array
    {
        return DB::table('product_supplier')
            ->where('supplier_id', $this->supplier->id)
            ->pluck('product_id')->all();
    }

    /** @return array<int,float> map product_id => unit cost */
    private function productCosts(): array
    {
        return DB::table('product_supplier')
            ->where('supplier_id', $this->supplier->id)
            ->pluck('purchase_price', 'product_id')
            ->map(fn($v) => (float) $v)
            ->all();
    }

    private function addToYear(array &$byYear, $year, $productId, $q, $r): void
    {
        $byYear[$year] ??= [];
        if (!isset($byYear[$year][$productId])) {
            $byYear[$year][$productId] = ['q' => 0, 'r' => 0.0];
        }
        $byYear[$year][$productId]['q'] += (int) $q;
        $byYear[$year][$productId]['r'] += (float) $r;
    }

    private function salesAggregate(): array
    {
        $ids = $this->supplierProductIds();
        if (empty($ids)) return ['revenue' => 0, 'units' => 0, 'cogs' => 0];

        $revenue = 0.0; $units = 0; $cogs = 0.0;
        $costs = $this->productCosts();

        $accumulate = function ($rows) use (&$revenue, &$units, &$cogs, $costs) {
            foreach ($rows as $r) {
                $revenue += (float) $r->r;
                $units += (int) $r->q;
                $cogs += (int) $r->q * ($costs[$r->product_id] ?? 0);
            }
        };

        $accumulate(DB::table('sale_items')
            ->whereIn('product_id', $ids)
            ->where('item_type', 'product')
            ->selectRaw('product_id, SUM(quantity) as q, SUM(quantity * price) as r')
            ->groupBy('product_id')->get());

        $accumulate(DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereIn('oi.product_id', $ids)
            ->where('oi.item_type', 'product')
            ->where('o.payment_status', 'paid')
            ->selectRaw('oi.product_id as product_id, SUM(oi.quantity) as q, SUM(oi.quantity * oi.unit_price) as r')
            ->groupBy('oi.product_id')->get());

        $accumulate(DB::table('reseller_sales_report_items')
            ->whereIn('product_id', $ids)
            ->selectRaw('product_id, SUM(quantity_sold) as q, SUM(quantity_sold * unit_price) as r')
            ->groupBy('product_id')->get());

        return ['revenue' => $revenue, 'units' => $units, 'cogs' => $cogs];
    }
}
