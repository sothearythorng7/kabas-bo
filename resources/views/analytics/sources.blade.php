@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title">
        <i class="bi bi-diagram-3"></i> {{ __('messages.analytics.sources.title') }}
    </h1>
    <p class="text-muted">{{ __('messages.analytics.sources.description') }}</p>

    @include('analytics.partials.period-picker')

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">In-house — {{ $start->format('d M Y') }} → {{ $end->format('d M Y') }}</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.analytics.sources.source') }}</th>
                        <th>{{ __('messages.analytics.sources.utm_campaign') }}</th>
                        <th class="text-end">{{ __('messages.analytics.sources.sessions') }}</th>
                        <th class="text-end">{{ __('messages.analytics.sources.share') }}</th>
                        <th class="text-end">{{ __('messages.analytics.sources.orders') }}</th>
                        <th class="text-end">{{ __('messages.analytics.sources.revenue') }}</th>
                        <th class="text-end">{{ __('messages.analytics.sources.conversion_rate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td>
                                <span class="badge bg-{{ ['direct'=>'secondary','organic'=>'success','social'=>'info','referral'=>'warning','email'=>'primary','paid'=>'danger','other'=>'dark'][$r->source_category] ?? 'secondary' }}">
                                    {{ $r->source_category }}
                                </span>
                            </td>
                            <td class="text-muted">{{ $r->utm_campaign ?: '—' }}</td>
                            <td class="text-end">{{ number_format($r->sessions) }}</td>
                            <td class="text-end">{{ $totalSessions > 0 ? number_format(100 * $r->sessions / $totalSessions, 1) : 0 }}%</td>
                            <td class="text-end">{{ number_format($r->orders) }}</td>
                            <td class="text-end">{{ number_format($r->revenue, 2) }}$</td>
                            <td class="text-end"><span class="badge {{ $r->conversion_rate >= 2 ? 'bg-success' : ($r->conversion_rate >= 0.5 ? 'bg-warning' : 'bg-secondary') }}">{{ $r->conversion_rate }}%</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('messages.analytics.overview.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0"><i class="bi bi-google"></i> {{ __('messages.analytics.sources.ga4_title') }}</h6>
        </div>
        <div class="card-body">
            @if(empty($ga4Report['available']) || ! $ga4Report['available'])
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i> {{ __('messages.analytics.sources.ga4_not_configured') }}
                    @if(!empty($ga4Report['error']))
                        <pre class="small mb-0 mt-2">{{ $ga4Report['error'] }}</pre>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Source</th><th>Medium</th>
                                <th class="text-end">Sessions</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Conversions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ga4Report['rows'] as $r)
                                <tr>
                                    <td>{{ $r['sessionSource'] ?? '—' }}</td>
                                    <td>{{ $r['sessionMedium'] ?? '—' }}</td>
                                    <td class="text-end">{{ number_format((float)($r['sessions'] ?? 0)) }}</td>
                                    <td class="text-end">{{ number_format((float)($r['totalRevenue'] ?? 0), 2) }}</td>
                                    <td class="text-end">{{ number_format((float)($r['conversions'] ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
