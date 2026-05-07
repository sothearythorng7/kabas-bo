<?php

namespace App\Http\Controllers;

use App\Services\Ga4AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Serves the 8 Back Office analytics dashboards.
 *
 * Data sources:
 *   - in-house: analytics_events / analytics_sessions / analytics_*_daily
 *     tables populated by kabas-site (same database).
 *   - GA4: Ga4AnalyticsService (degraded mode when service account absent).
 *
 * All queries ignore staff-flagged rows at read time via the
 * `applyNonStaff()` helper (aggregates already exclude them at compute time,
 * but raw-event reads still need the filter).
 */
class AnalyticsDashboardController extends Controller
{
    public function overview(Request $request, Ga4AnalyticsService $ga4)
    {
        [$start, $end] = $this->resolvePeriod($request);

        // Cards
        $sum = DB::table('analytics_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('
                SUM(visits) AS sessions,
                SUM(unique_visitors) AS unique_visitors,
                SUM(new_visitors) AS new_visitors,
                SUM(page_views) AS page_views,
                SUM(bounced_sessions) AS bounced_sessions,
                SUM(orders_paid) AS orders_paid,
                SUM(revenue) AS revenue,
                AVG(avg_session_duration) AS avg_session_duration
            ')
            ->first();

        $sessions = (int) ($sum->sessions ?? 0);
        $bounceRate = $sessions > 0 ? (100 * (int)($sum->bounced_sessions ?? 0) / $sessions) : 0;
        $conversionRate = $sessions > 0 ? (100 * (int)($sum->orders_paid ?? 0) / $sessions) : 0;
        $aov = ((int)($sum->orders_paid ?? 0)) > 0 ? ((float)$sum->revenue / (int)$sum->orders_paid) : 0;
        $rpv = ((int)($sum->unique_visitors ?? 0)) > 0 ? ((float)$sum->revenue / (int)$sum->unique_visitors) : 0;

        $kpis = [
            'sessions' => $sessions,
            'unique_visitors' => (int) ($sum->unique_visitors ?? 0),
            'new_visitors' => (int) ($sum->new_visitors ?? 0),
            'page_views' => (int) ($sum->page_views ?? 0),
            'bounce_rate' => round($bounceRate, 1),
            'avg_session_duration' => (int) ($sum->avg_session_duration ?? 0),
            'orders_paid' => (int) ($sum->orders_paid ?? 0),
            'revenue' => (float) ($sum->revenue ?? 0),
            'aov' => round($aov, 2),
            'rpv' => round($rpv, 2),
            'conversion_rate' => round($conversionRate, 2),
        ];

        // Live visitors: sessions with last_activity in the last 5 minutes (non-staff)
        $liveVisitors = (int) DB::table('analytics_sessions')
            ->where('is_staff', 0)
            ->where('last_activity_at', '>=', now()->subMinutes(5))
            ->count();

        // Daily series
        $daily = DB::table('analytics_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get(['date', 'visits', 'orders_paid', 'revenue']);

        // Sources (in-house, top 6 for donut)
        $sources = DB::table('analytics_source_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('source_category, SUM(sessions) AS sessions')
            ->groupBy('source_category')
            ->orderByDesc('sessions')
            ->get();

        // Devices (from raw sessions in period)
        $devices = DB::table('analytics_sessions')
            ->where('is_staff', 0)
            ->whereBetween('started_at', [$start, $end])
            ->selectRaw('device_type, COUNT(*) AS sessions')
            ->groupBy('device_type')
            ->orderByDesc('sessions')
            ->get();

        // Top 5 products viewed (over the period)
        $topViewed = DB::table('analytics_product_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('product_id, SUM(views) AS views, SUM(cart_adds) AS cart_adds, SUM(purchases) AS purchases')
            ->groupBy('product_id')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        $topPurchased = DB::table('analytics_product_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('product_id, SUM(purchases) AS purchases, SUM(units_sold) AS units_sold, SUM(revenue) AS revenue')
            ->groupBy('product_id')
            ->having('purchases', '>', 0)
            ->orderByDesc('purchases')
            ->limit(5)
            ->get();

        $topViewed = $this->attachProductNames($topViewed);
        $topPurchased = $this->attachProductNames($topPurchased);

        // Top 5 searches
        $topSearches = DB::table('analytics_search_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('term, SUM(count) AS count, SUM(zero_results_count) AS zero_results_count')
            ->groupBy('term')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Top geo (6 for donut)
        $topGeo = DB::table('analytics_geo_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('country_code, SUM(sessions) AS sessions')
            ->groupBy('country_code')
            ->orderByDesc('sessions')
            ->limit(6)
            ->get();

        // GA4 cross-check (period totals + daily series for the chart)
        $ga4Totals = $ga4->runReport(
            dimensions: [],
            metrics: ['sessions', 'activeUsers', 'newUsers', 'engagementRate', 'screenPageViews'],
            startDate: $start->toDateString(),
            endDate: $end->toDateString(),
            limit: 1,
        );
        $ga4Daily = $ga4->runReport(
            dimensions: ['date'],
            metrics: ['sessions'],
            startDate: $start->toDateString(),
            endDate: $end->toDateString(),
            limit: 100,
        );
        $ga4Realtime = $ga4->realtimeActiveUsers();

        // Index GA4 daily series by YYYY-MM-DD for chart overlay
        $ga4DailyByDate = [];
        if (! empty($ga4Daily['available']) && ! empty($ga4Daily['rows'])) {
            foreach ($ga4Daily['rows'] as $r) {
                if (! empty($r['date']) && strlen((string) $r['date']) === 8) {
                    $iso = substr($r['date'], 0, 4) . '-' . substr($r['date'], 4, 2) . '-' . substr($r['date'], 6, 2);
                    $ga4DailyByDate[$iso] = (float) ($r['sessions'] ?? 0);
                }
            }
        }

        return view('analytics.overview', compact(
            'start', 'end', 'kpis', 'liveVisitors',
            'daily', 'sources', 'devices', 'topViewed', 'topPurchased', 'topSearches', 'topGeo',
            'ga4Totals', 'ga4DailyByDate', 'ga4Realtime'
        ));
    }

    public function products(Request $request)
    {
        [$start, $end] = $this->resolvePeriod($request);

        $filter = $request->get('filter', ''); // '', 'viewed_not_purchased', 'purchased_not_viewed'
        $sort = $request->get('sort', 'views');
        $allowedSorts = ['views', 'unique_viewers', 'cart_adds', 'purchases', 'units_sold', 'revenue'];
        if (! in_array($sort, $allowedSorts, true)) $sort = 'views';

        $query = DB::table('analytics_product_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('
                product_id,
                SUM(views) AS views,
                SUM(unique_viewers) AS unique_viewers,
                SUM(cart_adds) AS cart_adds,
                SUM(purchases) AS purchases,
                SUM(units_sold) AS units_sold,
                SUM(revenue) AS revenue
            ')
            ->groupBy('product_id');

        if ($filter === 'viewed_not_purchased') {
            $query->having('views', '>', 0)->having('purchases', '=', 0);
        } elseif ($filter === 'purchased_not_viewed') {
            $query->having('views', '=', 0)->having('purchases', '>', 0);
        }

        $rows = $query->orderByDesc($sort)->limit(200)->get();

        foreach ($rows as $r) {
            $r->view_to_cart = $r->views > 0 ? round(100 * $r->cart_adds / $r->views, 1) : 0;
            $r->cart_to_purchase = $r->cart_adds > 0 ? round(100 * $r->purchases / $r->cart_adds, 1) : 0;
            $r->view_to_purchase = $r->views > 0 ? round(100 * $r->purchases / $r->views, 1) : 0;
        }

        $rows = $this->attachProductNames($rows);

        return view('analytics.products', compact('start', 'end', 'rows', 'filter', 'sort'));
    }

    public function sources(Request $request, Ga4AnalyticsService $ga4)
    {
        [$start, $end] = $this->resolvePeriod($request);

        $rows = DB::table('analytics_source_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('
                source_category,
                utm_campaign,
                SUM(sessions) AS sessions,
                SUM(orders) AS orders,
                SUM(revenue) AS revenue
            ')
            ->groupBy('source_category', 'utm_campaign')
            ->orderByDesc('sessions')
            ->get();

        foreach ($rows as $r) {
            $r->conversion_rate = $r->sessions > 0 ? round(100 * $r->orders / $r->sessions, 2) : 0;
        }

        $totalSessions = $rows->sum('sessions');

        // GA4 second opinion
        $ga4Report = $ga4->runReport(
            dimensions: ['sessionSource', 'sessionMedium'],
            metrics: ['sessions', 'totalRevenue', 'conversions'],
            startDate: $start->toDateString(),
            endDate: $end->toDateString(),
            limit: 20,
        );

        return view('analytics.sources', compact('start', 'end', 'rows', 'totalSessions', 'ga4Report'));
    }

    public function search(Request $request)
    {
        [$start, $end] = $this->resolvePeriod($request);

        $topTerms = DB::table('analytics_search_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('term, SUM(count) AS count, SUM(zero_results_count) AS zero_results_count, SUM(click_through_count) AS ctr_count')
            ->groupBy('term')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        $zeroResults = DB::table('analytics_search_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('term, SUM(zero_results_count) AS zero_results_count, SUM(count) AS count')
            ->groupBy('term')
            ->having('zero_results_count', '>', 0)
            ->orderByDesc('zero_results_count')
            ->limit(20)
            ->get();

        return view('analytics.search', compact('start', 'end', 'topTerms', 'zeroResults'));
    }

    public function customers(Request $request, Ga4AnalyticsService $ga4)
    {
        [$start, $end] = $this->resolvePeriod($request);

        $totals = DB::table('analytics_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('SUM(new_customers) AS new_customers, SUM(returning_customers) AS returning_customers')
            ->first();

        $newCustomers = (int) ($totals->new_customers ?? 0);
        $returningCustomers = (int) ($totals->returning_customers ?? 0);

        // LTV: sum of revenue / distinct paying customers, over all time on paid orders
        $ltvRow = DB::table('orders')
            ->where('payment_status', 'paid')
            ->whereNotNull('customer_id')
            ->selectRaw('COALESCE(SUM(total), 0) AS total_rev, COUNT(DISTINCT customer_id) AS unique_customers')
            ->first();
        $avgLtv = (int) $ltvRow->unique_customers > 0
            ? round($ltvRow->total_rev / $ltvRow->unique_customers, 2)
            : 0;

        // Repeat rate
        $repeatCustomers = DB::table('orders')
            ->where('payment_status', 'paid')
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) AS orders_count')
            ->groupBy('customer_id')
            ->having('orders_count', '>', 1)
            ->get()
            ->count();
        $totalCustomers = (int) $ltvRow->unique_customers;
        $repeatRate = $totalCustomers > 0 ? round(100 * $repeatCustomers / $totalCustomers, 1) : 0;

        // Avg days between orders (on repeat customers)
        $avgDaysBetween = DB::select("
            SELECT AVG(gap_days) AS avg_gap FROM (
                SELECT DATEDIFF(o2.paid_at, o1.paid_at) AS gap_days
                FROM orders o1
                JOIN orders o2 ON o2.customer_id = o1.customer_id
                WHERE o1.payment_status = 'paid'
                  AND o2.payment_status = 'paid'
                  AND o1.paid_at < o2.paid_at
                  AND NOT EXISTS (
                    SELECT 1 FROM orders o3
                    WHERE o3.customer_id = o1.customer_id
                      AND o3.payment_status = 'paid'
                      AND o3.paid_at > o1.paid_at
                      AND o3.paid_at < o2.paid_at
                  )
            ) gaps
        ");
        $avgGap = $avgDaysBetween[0]->avg_gap ?? null;

        // Simple cohort: customers grouped by month of first paid order, count first-month vs returning
        $cohort = DB::select("
            SELECT DATE_FORMAT(first_order.first_paid, '%Y-%m') AS cohort_month,
                   COUNT(DISTINCT first_order.customer_id) AS cohort_size,
                   SUM(CASE WHEN later.customer_id IS NOT NULL THEN 1 ELSE 0 END) AS retained
            FROM (
                SELECT customer_id, MIN(paid_at) AS first_paid
                FROM orders
                WHERE payment_status = 'paid' AND customer_id IS NOT NULL
                GROUP BY customer_id
            ) first_order
            LEFT JOIN (
                SELECT DISTINCT customer_id FROM orders
                WHERE payment_status = 'paid'
                GROUP BY customer_id
                HAVING COUNT(*) > 1
            ) later ON later.customer_id = first_order.customer_id
            GROUP BY cohort_month
            ORDER BY cohort_month DESC
            LIMIT 12
        ");

        // GA4 user metrics for the period
        $ga4Users = $ga4->runReport(
            dimensions: [],
            metrics: ['activeUsers', 'newUsers', 'sessions', 'engagementRate', 'userEngagementDuration'],
            startDate: $start->toDateString(),
            endDate: $end->toDateString(),
            limit: 1,
        );

        // GA4 new vs returning split (newVsReturning dimension)
        $ga4NewReturning = $ga4->runReport(
            dimensions: ['newVsReturning'],
            metrics: ['activeUsers', 'sessions'],
            startDate: $start->toDateString(),
            endDate: $end->toDateString(),
            limit: 5,
        );

        return view('analytics.customers', compact(
            'start', 'end',
            'newCustomers', 'returningCustomers',
            'avgLtv', 'repeatRate', 'repeatCustomers', 'totalCustomers',
            'avgGap', 'cohort',
            'ga4Users', 'ga4NewReturning'
        ));
    }

    public function geo(Request $request)
    {
        [$start, $end] = $this->resolvePeriod($request);

        $rows = DB::table('analytics_geo_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('country_code, SUM(sessions) AS sessions, SUM(orders) AS orders, SUM(revenue) AS revenue')
            ->groupBy('country_code')
            ->orderByDesc('sessions')
            ->get();

        foreach ($rows as $r) {
            $r->conversion_rate = $r->sessions > 0 ? round(100 * $r->orders / $r->sessions, 2) : 0;
        }

        return view('analytics.geo', compact('start', 'end', 'rows'));
    }

    public function checkout(Request $request)
    {
        [$start, $end] = $this->resolvePeriod($request);

        // Funnel steps based on unique visitor counts across event types in the period
        $stepCounts = function (array $eventTypes) use ($start, $end) {
            return (int) DB::table('analytics_events')
                ->where('is_staff', 0)
                ->whereIn('event_type', $eventTypes)
                ->whereBetween('created_at', [$start, $end])
                ->distinct('visitor_id')
                ->count('visitor_id');
        };

        $steps = [
            ['key' => 'page_view', 'label' => 'messages.analytics.funnel.visited_site', 'visitors' => $stepCounts(['page_view'])],
            ['key' => 'product_view', 'label' => 'messages.analytics.funnel.viewed_product', 'visitors' => $stepCounts(['product_view'])],
            ['key' => 'add_to_cart', 'label' => 'messages.analytics.funnel.added_to_cart', 'visitors' => $stepCounts(['add_to_cart'])],
            ['key' => 'checkout_start', 'label' => 'messages.analytics.funnel.started_checkout', 'visitors' => $stepCounts(['checkout_start'])],
            ['key' => 'order_placed', 'label' => 'messages.analytics.funnel.placed_order', 'visitors' => $stepCounts(['order_placed'])],
        ];

        $base = $steps[0]['visitors'];
        foreach ($steps as &$s) {
            $s['percent_of_top'] = $base > 0 ? round(100 * $s['visitors'] / $base, 1) : 0;
        }
        unset($s);

        // Step-to-step drop-offs
        $dropOffs = [];
        for ($i = 0; $i < count($steps) - 1; $i++) {
            $from = $steps[$i]['visitors'];
            $to = $steps[$i + 1]['visitors'];
            $dropOffs[] = [
                'from' => $steps[$i]['label'],
                'to' => $steps[$i + 1]['label'],
                'conversion' => $from > 0 ? round(100 * $to / $from, 1) : 0,
                'dropped' => max(0, $from - $to),
            ];
        }

        return view('analytics.checkout', compact('start', 'end', 'steps', 'dropOffs'));
    }

    public function marketing(Request $request)
    {
        [$start, $end] = $this->resolvePeriod($request);

        // Promo code performance: join orders applied_promotion_code with promotion_codes
        $promoCodes = DB::table('orders')
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->whereNotNull('applied_promotion_code')
            ->where('applied_promotion_code', '!=', '')
            ->selectRaw('applied_promotion_code, COUNT(*) AS orders, SUM(total) AS revenue')
            ->groupBy('applied_promotion_code')
            ->orderByDesc('orders')
            ->limit(30)
            ->get();

        // UTM campaigns (in-house)
        $utm = DB::table('analytics_source_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('utm_campaign', '!=', '')
            ->selectRaw("utm_campaign, source_category, SUM(sessions) AS sessions, SUM(orders) AS orders, SUM(revenue) AS revenue")
            ->groupBy('utm_campaign', 'source_category')
            ->orderByDesc('sessions')
            ->limit(30)
            ->get();

        foreach ($utm as $u) {
            $u->conversion_rate = $u->sessions > 0 ? round(100 * $u->orders / $u->sessions, 2) : 0;
        }

        // Abandoned-cart recovery: use promotion_codes with CART- prefix as our signal
        $abandonedSent = 0;
        $abandonedConverted = 0;
        try {
            $abandonedSent = DB::table('abandoned_cart_reminders')->whereBetween('sent_at', [$start, $end])->count();
        } catch (\Throwable) {}

        try {
            $abandonedConverted = DB::table('orders')
                ->where('payment_status', 'paid')
                ->whereBetween('paid_at', [$start, $end])
                ->where('applied_promotion_code', 'like', 'CART-%')
                ->count();
        } catch (\Throwable) {}

        $abandonedRate = $abandonedSent > 0 ? round(100 * $abandonedConverted / $abandonedSent, 2) : 0;

        // Payment recovery: unique orders that got a recovery reminder sent and are now paid
        $recoverySent = 0;
        $recoveryConverted = 0;
        try {
            $recoverySent = DB::table('order_payment_recovery_reminders')->whereBetween('sent_at', [$start, $end])->count();
            $recoveryConverted = DB::table('order_payment_recovery_reminders as r')
                ->join('orders as o', 'o.id', '=', 'r.order_id')
                ->whereBetween('r.sent_at', [$start, $end])
                ->where('o.payment_status', 'paid')
                ->distinct('o.id')
                ->count('o.id');
        } catch (\Throwable) {}

        $recoveryRate = $recoverySent > 0 ? round(100 * $recoveryConverted / $recoverySent, 2) : 0;

        return view('analytics.marketing', compact(
            'start', 'end',
            'promoCodes', 'utm',
            'abandonedSent', 'abandonedConverted', 'abandonedRate',
            'recoverySent', 'recoveryConverted', 'recoveryRate'
        ));
    }

    /**
     * Resolves the start/end period from the request (default = last 30 days).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolvePeriod(Request $request): array
    {
        $preset = $request->get('period', '30d');

        if ($preset === 'custom') {
            $start = CarbonImmutable::parse($request->get('start', CarbonImmutable::today()->subDays(29)->toDateString()))->startOfDay();
            $end = CarbonImmutable::parse($request->get('end', CarbonImmutable::today()->toDateString()))->endOfDay();
        } else {
            $map = ['7d' => 6, '30d' => 29, '90d' => 89, 'ytd' => null];
            if ($preset === 'ytd') {
                $start = CarbonImmutable::now()->startOfYear();
            } else {
                $days = $map[$preset] ?? 29;
                $start = CarbonImmutable::today()->subDays($days);
            }
            $end = CarbonImmutable::today()->endOfDay();
        }

        return [$start, $end];
    }

    /**
     * Resolves product names from backoffice table by product IDs on a collection.
     */
    protected function attachProductNames($rows)
    {
        $ids = collect($rows)->pluck('product_id')->filter()->unique()->all();
        if (! $ids) return $rows;
        $products = DB::table('products')
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'ean'])
            ->keyBy('id');

        foreach ($rows as $r) {
            $p = $products->get($r->product_id);
            if ($p) {
                $n = $p->name;
                if ($n && $n[0] === '{') {
                    $decoded = json_decode($n, true);
                    $n = $decoded['fr'] ?? $decoded['en'] ?? array_values($decoded)[0] ?? $p->ean;
                }
                $r->product_name = $n ?: $p->ean;
                $r->product_ean = $p->ean;
            } else {
                $r->product_name = '#'.$r->product_id;
                $r->product_ean = null;
            }
        }

        return $rows;
    }
}
