<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Back Office read-side analytics config
    |--------------------------------------------------------------------------
    |
    | The BO reads analytics data from two sources:
    |   - in-house tables written by the public site (analytics_events, _sessions,
    |     _daily, _product_daily, _source_daily, _geo_daily, _search_daily)
    |   - Google Analytics 4 Data API (via service account), used for sources
    |     breakdown and as a second opinion on traffic
    |
    | This file centralizes GA4 credentials and cache durations.
    |
    */

    'enabled' => env('ANALYTICS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Google Analytics 4
    |--------------------------------------------------------------------------
    |
    | property_id        GA4 property id, formatted "properties/XXXXXXXXX"
    | credentials_path   Absolute path to the service account JSON
    |
    | If either is missing, Ga4AnalyticsService runs in degraded mode: every
    | call returns an empty result with `available=false`. Dashboards should
    | display "GA4 non configuré" instead of failing.
    |
    */
    'ga4' => [
        'property_id' => env('GA4_PROPERTY_ID'),
        'credentials_path' => env('GA4_CREDENTIALS_PATH', storage_path('app/ga4/credentials.json')),
        'cache_ttl_seconds' => env('GA4_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard period defaults
    |--------------------------------------------------------------------------
    */
    'default_period_days' => 30,

];
