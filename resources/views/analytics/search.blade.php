@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title">
        <i class="bi bi-search"></i> {{ __('messages.analytics.search.title') }}
    </h1>
    <p class="text-muted">{{ __('messages.analytics.search.description') }}</p>

    @include('analytics.partials.period-picker')

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">{{ __('messages.analytics.search.top_terms') }}</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('messages.analytics.search.term') }}</th>
                                <th class="text-end">{{ __('messages.analytics.search.count') }}</th>
                                <th class="text-end">{{ __('messages.analytics.search.zero') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topTerms as $t)
                                <tr>
                                    <td>{{ $t->term }}</td>
                                    <td class="text-end fw-bold">{{ number_format($t->count) }}</td>
                                    <td class="text-end">
                                        @if($t->zero_results_count)
                                            <span class="text-warning">{{ number_format($t->zero_results_count) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">{{ __('messages.analytics.overview.no_data') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning-subtle">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> {{ __('messages.analytics.search.zero_results') }}</h6>
                </div>
                <div class="card-body pb-0">
                    <p class="text-muted small">{{ __('messages.analytics.search.zero_results_hint') }}</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('messages.analytics.search.term') }}</th>
                                <th class="text-end">{{ __('messages.analytics.search.zero') }}</th>
                                <th class="text-end">{{ __('messages.analytics.search.count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($zeroResults as $t)
                                <tr>
                                    <td>{{ $t->term }}</td>
                                    <td class="text-end fw-bold text-warning">{{ number_format($t->zero_results_count) }}</td>
                                    <td class="text-end text-muted">{{ number_format($t->count) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">{{ __('messages.analytics.overview.no_data') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
