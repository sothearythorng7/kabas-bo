@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title">
        <i class="bi bi-funnel"></i> {{ __('messages.analytics.funnel.title') }}
    </h1>
    <p class="text-muted">{{ __('messages.analytics.funnel.description') }}</p>

    @include('analytics.partials.period-picker')

    <div class="card mb-4">
        <div class="card-body">
            @if($steps[0]['visitors'] === 0)
                <p class="text-muted mb-0">{{ __('messages.analytics.overview.no_data') }}</p>
            @else
                @foreach($steps as $i => $s)
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 220px;" class="text-truncate">
                            <strong>{{ $i + 1 }}.</strong> {{ __($s['label']) }}
                        </div>
                        <div class="flex-grow-1 mx-3">
                            <div class="progress" style="height: 32px;">
                                <div class="progress-bar {{ $i === 0 ? 'bg-primary' : ($i === count($steps) - 1 ? 'bg-success' : 'bg-info') }}"
                                     role="progressbar"
                                     style="width: {{ $s['percent_of_top'] }}%">
                                    {{ number_format($s['visitors']) }} ({{ $s['percent_of_top'] }}%)
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h6 class="mb-0">{{ __('messages.analytics.funnel.dropoff_title') }}</h6></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.analytics.funnel.dropoff_from') }}</th>
                        <th>{{ __('messages.analytics.funnel.dropoff_to') }}</th>
                        <th class="text-end">{{ __('messages.analytics.funnel.dropoff_conversion') }}</th>
                        <th class="text-end">{{ __('messages.analytics.funnel.dropoff_dropped') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dropOffs as $d)
                        <tr>
                            <td>{{ __($d['from']) }}</td>
                            <td>{{ __($d['to']) }}</td>
                            <td class="text-end">
                                <span class="badge {{ $d['conversion'] >= 50 ? 'bg-success' : ($d['conversion'] >= 20 ? 'bg-warning' : 'bg-danger') }}">{{ $d['conversion'] }}%</span>
                            </td>
                            <td class="text-end text-muted">-{{ number_format($d['dropped']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
