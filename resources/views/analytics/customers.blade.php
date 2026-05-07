@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title">
        <i class="bi bi-people"></i> {{ __('messages.analytics.customers.title') }}
    </h1>
    <p class="text-muted">{{ __('messages.analytics.customers.description') }}</p>

    @include('analytics.partials.period-picker')

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.new_customers') }}</div>
                    <div class="display-6 fw-bold text-success">{{ number_format($newCustomers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.returning_customers') }}</div>
                    <div class="display-6 fw-bold text-info">{{ number_format($returningCustomers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.avg_ltv') }}</div>
                    <div class="display-6 fw-bold">{{ number_format($avgLtv, 2) }}$</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.repeat_rate') }}</div>
                    <div class="display-6 fw-bold">{{ number_format($repeatRate, 1) }}%</div>
                    <div class="small text-muted">{{ number_format($repeatCustomers) }} / {{ number_format($totalCustomers) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.avg_gap') }}</div>
                    <div class="display-6 fw-bold">{{ $avgGap !== null ? number_format($avgGap, 1) : '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- GA4 — visitor engagement --}}
    @php
        $ga4Available = !empty($ga4Users['available']) && !empty($ga4Users['rows']);
        $ga4Row = $ga4Available ? $ga4Users['rows'][0] : [];
        $ga4Engagement = (int) round((float)($ga4Row['userEngagementDuration'] ?? 0));
        $ga4EngMin = floor($ga4Engagement / 60);
        $ga4EngSec = $ga4Engagement % 60;

        $ga4Splits = ['new' => 0, 'returning' => 0];
        if (!empty($ga4NewReturning['available'])) {
            foreach ($ga4NewReturning['rows'] ?? [] as $r) {
                $key = $r['newVsReturning'] ?? '';
                if (in_array($key, ['new','returning'], true)) {
                    $ga4Splits[$key] = (int) round((float)($r['activeUsers'] ?? 0));
                }
            }
        }
        $ga4SplitTotal = $ga4Splits['new'] + $ga4Splits['returning'];
    @endphp
    <div class="card mb-4 border-info">
        <div class="card-header bg-info bg-opacity-10 d-flex align-items-center">
            <i class="bi bi-google me-2"></i>
            <h6 class="mb-0">{{ __('messages.analytics.customers.ga4_section') }}</h6>
        </div>
        <div class="card-body">
            @if(!$ga4Available)
                <p class="text-muted mb-0">{{ __('messages.analytics.customers.ga4_unavailable') }}
                    @if(!empty($ga4Users['error']))
                        <small class="d-block mt-1">{{ $ga4Users['error'] }}</small>
                    @endif
                </p>
            @else
                <div class="row g-3">
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.ga4_active_users') }}</div>
                        <div class="display-6 fw-bold">{{ number_format((float)($ga4Row['activeUsers'] ?? 0)) }}</div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.ga4_new_users') }}</div>
                        <div class="display-6 fw-bold text-success">{{ number_format((float)($ga4Row['newUsers'] ?? 0)) }}</div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.ga4_returning_users') }}</div>
                        <div class="display-6 fw-bold text-info">{{ number_format($ga4Splits['returning']) }}</div>
                        @if($ga4SplitTotal > 0)
                            <div class="small text-muted">{{ number_format(100 * $ga4Splits['returning'] / $ga4SplitTotal, 1) }}% du split</div>
                        @endif
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.ga4_sessions') }}</div>
                        <div class="display-6 fw-bold">{{ number_format((float)($ga4Row['sessions'] ?? 0)) }}</div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.ga4_engagement_rate') }}</div>
                        <div class="display-6 fw-bold">{{ number_format(100 * (float)($ga4Row['engagementRate'] ?? 0), 1) }}<span class="fs-5">%</span></div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.customers.ga4_engagement_duration') }}</div>
                        <div class="display-6 fw-bold">{{ $ga4EngMin }}m {{ str_pad($ga4EngSec, 2, '0', STR_PAD_LEFT) }}s</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h6 class="mb-0">{{ __('messages.analytics.customers.cohort_title') }}</h6></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.analytics.customers.cohort_month') }}</th>
                        <th class="text-end">{{ __('messages.analytics.customers.cohort_size') }}</th>
                        <th class="text-end">{{ __('messages.analytics.customers.cohort_retained') }}</th>
                        <th class="text-end">{{ __('messages.analytics.customers.cohort_rate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cohort as $c)
                        <tr>
                            <td>{{ $c->cohort_month }}</td>
                            <td class="text-end">{{ number_format($c->cohort_size) }}</td>
                            <td class="text-end">{{ number_format($c->retained) }}</td>
                            <td class="text-end">
                                @php $rate = $c->cohort_size > 0 ? round(100 * $c->retained / $c->cohort_size, 1) : 0; @endphp
                                <span class="badge {{ $rate >= 20 ? 'bg-success' : ($rate >= 10 ? 'bg-warning' : 'bg-secondary') }}">{{ $rate }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('messages.analytics.overview.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
