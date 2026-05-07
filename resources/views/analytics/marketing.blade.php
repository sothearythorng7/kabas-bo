@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title">
        <i class="bi bi-megaphone"></i> {{ __('messages.analytics.marketing.title') }}
    </h1>
    <p class="text-muted">{{ __('messages.analytics.marketing.description') }}</p>

    @include('analytics.partials.period-picker')

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100 border-info">
                <div class="card-header bg-info-subtle">
                    <h6 class="mb-0"><i class="bi bi-cart-x"></i> {{ __('messages.analytics.marketing.abandoned_cart') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-muted small">{{ __('messages.analytics.marketing.reminders_sent') }}</div>
                            <div class="fs-3 fw-bold">{{ number_format($abandonedSent) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">{{ __('messages.analytics.marketing.converted') }}</div>
                            <div class="fs-3 fw-bold text-success">{{ number_format($abandonedConverted) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">{{ __('messages.analytics.marketing.conversion_rate') }}</div>
                            <div class="fs-3 fw-bold">{{ $abandonedRate }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning-subtle">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> {{ __('messages.analytics.marketing.payment_recovery') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-muted small">{{ __('messages.analytics.marketing.reminders_sent') }}</div>
                            <div class="fs-3 fw-bold">{{ number_format($recoverySent) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">{{ __('messages.analytics.marketing.converted') }}</div>
                            <div class="fs-3 fw-bold text-success">{{ number_format($recoveryConverted) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">{{ __('messages.analytics.marketing.conversion_rate') }}</div>
                            <div class="fs-3 fw-bold">{{ $recoveryRate }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">{{ __('messages.analytics.marketing.promo_codes') }}</h6></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promoCodes as $p)
                        <tr>
                            <td><code>{{ $p->applied_promotion_code }}</code></td>
                            <td class="text-end">{{ number_format($p->orders) }}</td>
                            <td class="text-end fw-bold">{{ number_format($p->revenue, 2) }}$</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">{{ __('messages.analytics.overview.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h6 class="mb-0">{{ __('messages.analytics.marketing.utm_campaigns') }}</h6></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Campaign</th>
                        <th>Source</th>
                        <th class="text-end">Sessions</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">Conversion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($utm as $u)
                        <tr>
                            <td><strong>{{ $u->utm_campaign }}</strong></td>
                            <td class="text-muted">{{ $u->source_category }}</td>
                            <td class="text-end">{{ number_format($u->sessions) }}</td>
                            <td class="text-end">{{ number_format($u->orders) }}</td>
                            <td class="text-end">{{ number_format($u->revenue, 2) }}$</td>
                            <td class="text-end">
                                <span class="badge {{ $u->conversion_rate >= 2 ? 'bg-success' : ($u->conversion_rate >= 0.5 ? 'bg-warning' : 'bg-secondary') }}">{{ $u->conversion_rate }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ __('messages.analytics.overview.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
