{{--
    Shared period picker. The parent view passes $start, $end (CarbonImmutable)
    and optionally extra hidden fields via $extraFields (array of key => value).
    The current preset is inferred from query string — defaults to 30d.
--}}
@php
    $currentPreset = request('period', '30d');
    $customVisible = $currentPreset === 'custom';
    $extraFields = $extraFields ?? [];
@endphp

<form method="GET" action="" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label class="form-label small mb-1">{{ __('messages.analytics.period.apply') }}</label>
        <select name="period" class="form-select" onchange="this.form.submit()">
            <option value="7d"  @selected($currentPreset === '7d')>{{ __('messages.analytics.period.last_7d') }}</option>
            <option value="30d" @selected($currentPreset === '30d')>{{ __('messages.analytics.period.last_30d') }}</option>
            <option value="90d" @selected($currentPreset === '90d')>{{ __('messages.analytics.period.last_90d') }}</option>
            <option value="ytd" @selected($currentPreset === 'ytd')>{{ __('messages.analytics.period.ytd') }}</option>
            <option value="custom" @selected($currentPreset === 'custom')>{{ __('messages.analytics.period.custom') }}</option>
        </select>
    </div>
    <div class="col-auto" id="custom-period-fields" style="{{ $customVisible ? '' : 'display:none;' }}">
        <label class="form-label small mb-1">{{ __('messages.analytics.period.from') }}</label>
        <input type="date" name="start" value="{{ request('start', $start->toDateString()) }}" class="form-control">
    </div>
    <div class="col-auto" style="{{ $customVisible ? '' : 'display:none;' }}">
        <label class="form-label small mb-1">{{ __('messages.analytics.period.to') }}</label>
        <input type="date" name="end" value="{{ request('end', $end->toDateString()) }}" class="form-control">
    </div>
    @if($customVisible)
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">{{ __('messages.analytics.period.apply') }}</button>
    </div>
    @endif
    <div class="col-auto ms-auto text-muted small">
        {{ $start->format('d M Y') }} — {{ $end->format('d M Y') }}
    </div>
    @foreach($extraFields as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
</form>

<script>
    (function () {
        var sel = document.querySelector('select[name="period"]');
        if (!sel) return;
        sel.addEventListener('change', function () {
            // If user picks "custom", reveal the date fields instead of submitting immediately
            if (this.value === 'custom') {
                document.getElementById('custom-period-fields').style.display = '';
                document.querySelectorAll('#custom-period-fields ~ .col-auto').forEach(function (el, i) {
                    if (i === 0 || i === 1) el.style.display = '';
                });
                // cancel the auto-submit from onchange
                return false;
            }
        });
    })();
</script>
