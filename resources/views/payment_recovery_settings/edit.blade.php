@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">
        <i class="bi bi-clock-history"></i> {{ __('messages.payment_recovery.title') }}
    </h1>

    <p class="text-muted">{{ __('messages.payment_recovery.description') }}</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <ul class="nav nav-tabs" id="paymentRecoveryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                <i class="bi bi-gear"></i> {{ __('messages.email_stats.tab_settings') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">
                <i class="bi bi-graph-up"></i> {{ __('messages.email_stats.tab_stats') }}
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="paymentRecoveryTabsContent">
    <div class="tab-pane fade show active" id="settings" role="tabpanel" aria-labelledby="settings-tab">

    <form action="{{ route('payment-recovery-settings.update') }}" method="POST" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-check form-switch mb-4">
                <input type="checkbox" class="form-check-input" role="switch" id="enabled" name="enabled" value="1" @checked($setting->enabled)>
                <label class="form-check-label fw-bold" for="enabled">{{ __('messages.payment_recovery.enabled') }}</label>
                <div class="form-text small">{{ __('messages.payment_recovery.enabled_hint') }}</div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.payment_recovery.delay_hours') }}</label>
                        <input type="number" name="delay_hours" class="form-control" min="1" max="720" value="{{ old('delay_hours', $setting->delay_hours) }}" required>
                        <div class="form-text small">{{ __('messages.payment_recovery.delay_hint') }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.payment_recovery.validity_days') }}</label>
                        <input type="number" name="link_validity_days" class="form-control" min="1" max="30" value="{{ old('link_validity_days', $setting->link_validity_days) }}" required>
                        <div class="form-text small">{{ __('messages.payment_recovery.validity_hint') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-header mt-2 border-top">
            <h5 class="mb-0"><i class="bi bi-envelope-paper"></i> {{ __('messages.payment_recovery.template_title') }}</h5>
            <div class="small text-muted mt-1">
                {{ __('messages.payment_recovery.placeholders_hint') }} :
                <code>:order_number</code>, <code>:expiry_date</code>, <code>:resume_url</code>
            </div>
        </div>
        <div class="card-body">
            @php $locales = ['fr' => 'Français', 'en' => 'English']; @endphp

            @foreach($locales as $loc => $label)
                <h6 class="mt-3">{{ $label }}</h6>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('messages.payment_recovery.field_subject') }}</label>
                        <input type="text" name="subject[{{ $loc }}]" class="form-control" value="{{ old('subject.'.$loc, $setting->subject[$loc] ?? '') }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('messages.payment_recovery.field_heading') }}</label>
                        <input type="text" name="heading[{{ $loc }}]" class="form-control" value="{{ old('heading.'.$loc, $setting->heading[$loc] ?? '') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('messages.payment_recovery.field_cta') }}</label>
                        <input type="text" name="cta_label[{{ $loc }}]" class="form-control" value="{{ old('cta_label.'.$loc, $setting->cta_label[$loc] ?? '') }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.payment_recovery.field_intro') }}</label>
                    <textarea name="intro_body[{{ $loc }}]" class="form-control" rows="4" required>{{ old('intro_body.'.$loc, $setting->intro_body[$loc] ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.payment_recovery.field_footer') }}</label>
                    <textarea name="footer_text[{{ $loc }}]" class="form-control" rows="2" required>{{ old('footer_text.'.$loc, $setting->footer_text[$loc] ?? '') }}</textarea>
                </div>
                @if(!$loop->last)
                    <hr>
                @endif
            @endforeach
        </div>

        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> {{ __('messages.btn.save') }}</button>
        </div>
    </form>

    </div>{{-- /#settings tab-pane --}}

    <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
        @include('shared._email_stats_tab', ['chartId' => 'paymentRecoveryStatsChart'])
    </div>

    </div>{{-- /.tab-content --}}
</div>
@endsection
