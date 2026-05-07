<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Google Analytics Data API for read-side dashboards.
 *
 * Runs in degraded mode when the SDK package (google/analytics-data) is not
 * installed or credentials are missing — every call returns an empty result
 * with `available=false`, so dashboards can render "GA4 non configuré"
 * instead of throwing.
 *
 * To activate:
 *   1. composer require google/analytics-data
 *   2. Place service account JSON at storage/app/ga4/credentials.json
 *      (or override GA4_CREDENTIALS_PATH in .env)
 *   3. Set GA4_PROPERTY_ID=properties/XXXXXXXXX in .env
 *   4. Grant the service account "Viewer" access on the GA4 property
 */
class Ga4AnalyticsService
{
    public function isAvailable(): bool
    {
        if (! config('analytics.enabled', true)) return false;
        if (! config('analytics.ga4.property_id')) return false;
        $credPath = config('analytics.ga4.credentials_path');
        if (! $credPath || ! is_file($credPath)) return false;
        if (! class_exists(\Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient::class)) return false;
        return true;
    }

    /**
     * Run a GA4 report. Returns normalized rows [{dim1, dim2, metric1, metric2}].
     *
     * @param array $dimensions   e.g. ['sessionSource', 'sessionMedium']
     * @param array $metrics      e.g. ['sessions', 'totalRevenue']
     * @param string $startDate   'YYYY-MM-DD' or GA4 relative ('7daysAgo')
     * @param string $endDate     'YYYY-MM-DD' or 'today'
     * @param int|null $limit     Max rows (null = GA4 default 10000)
     */
    public function runReport(array $dimensions, array $metrics, string $startDate, string $endDate, ?int $limit = null): array
    {
        if (! $this->isAvailable()) {
            return ['available' => false, 'rows' => [], 'totals' => []];
        }

        $cacheKey = 'ga4:'.md5(json_encode([$dimensions, $metrics, $startDate, $endDate, $limit]));

        return Cache::remember($cacheKey, (int) config('analytics.ga4.cache_ttl_seconds', 3600), function () use ($dimensions, $metrics, $startDate, $endDate, $limit) {
            try {
                $client = $this->client();
                if (! $client) {
                    return ['available' => false, 'rows' => [], 'totals' => []];
                }

                $request = new \Google\Analytics\Data\V1beta\RunReportRequest([
                    'property' => config('analytics.ga4.property_id'),
                    'dimensions' => array_map(
                        fn ($d) => new \Google\Analytics\Data\V1beta\Dimension(['name' => $d]),
                        $dimensions
                    ),
                    'metrics' => array_map(
                        fn ($m) => new \Google\Analytics\Data\V1beta\Metric(['name' => $m]),
                        $metrics
                    ),
                    'date_ranges' => [new \Google\Analytics\Data\V1beta\DateRange([
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ])],
                    'limit' => $limit,
                ]);

                $response = $client->runReport($request);

                $rows = [];
                foreach ($response->getRows() as $row) {
                    $out = [];
                    foreach ($row->getDimensionValues() as $i => $dv) {
                        $out[$dimensions[$i]] = $dv->getValue();
                    }
                    foreach ($row->getMetricValues() as $i => $mv) {
                        $out[$metrics[$i]] = is_numeric($mv->getValue()) ? (float) $mv->getValue() : $mv->getValue();
                    }
                    $rows[] = $out;
                }

                return ['available' => true, 'rows' => $rows, 'totals' => []];
            } catch (\Throwable $e) {
                Log::warning('GA4 runReport failed', ['error' => $e->getMessage()]);
                return ['available' => false, 'rows' => [], 'totals' => [], 'error' => $e->getMessage()];
            }
        });
    }

    public function realtimeActiveUsers(): int
    {
        if (! $this->isAvailable()) return 0;

        return (int) Cache::remember('ga4:realtime', 60, function () {
            try {
                $client = $this->client();
                if (! $client) return 0;

                $request = new \Google\Analytics\Data\V1beta\RunRealtimeReportRequest([
                    'property' => config('analytics.ga4.property_id'),
                    'metrics' => [new \Google\Analytics\Data\V1beta\Metric(['name' => 'activeUsers'])],
                ]);
                $resp = $client->runRealtimeReport($request);

                foreach ($resp->getRows() as $row) {
                    foreach ($row->getMetricValues() as $mv) {
                        return (int) $mv->getValue();
                    }
                }
                return 0;
            } catch (\Throwable $e) {
                Log::warning('GA4 realtime failed', ['error' => $e->getMessage()]);
                return 0;
            }
        });
    }

    private function client()
    {
        if (! class_exists(\Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient::class)) {
            return null;
        }
        $credPath = config('analytics.ga4.credentials_path');
        if (! $credPath || ! is_file($credPath)) {
            return null;
        }
        return new \Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient([
            'credentials' => $credPath,
        ]);
    }
}
