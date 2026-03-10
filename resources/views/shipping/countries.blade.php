@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="crud_title mb-0">{{ __('messages.shipping.countries_title') }}</h1>
            <p class="text-muted mb-0">{{ __('messages.shipping.countries_subtitle') }}</p>
        </div>
        <span class="badge bg-primary fs-6" id="globalCounter">{{ $activeCount }} {{ __('messages.shipping.enabled_countries') }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('shipping-countries.update') }}" method="POST">
        @csrf

        <div class="mb-3">
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> {{ __('messages.shipping.save_countries') }}</button>
        </div>

        @foreach($countries as $continent => $continentCountries)
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center">
                    <div class="form-check me-3">
                        <input class="form-check-input continent-toggle"
                               type="checkbox"
                               id="continent_{{ Str::slug($continent) }}"
                               data-continent="{{ Str::slug($continent) }}">
                        <label class="form-check-label fw-bold" for="continent_{{ Str::slug($continent) }}">
                            {{ $continent }} ({{ $continentCountries->count() }})
                        </label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($continentCountries as $country)
                            <div class="col-md-3 col-sm-6">
                                <div class="form-check">
                                    <input class="form-check-input country-check country-{{ Str::slug($continent) }}"
                                           type="checkbox"
                                           name="countries[]"
                                           value="{{ $country->id }}"
                                           id="country_{{ $country->id }}"
                                           data-continent="{{ Str::slug($continent) }}"
                                           {{ $country->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="country_{{ $country->id }}">
                                        {{ $country->code }} - {{ $country->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mb-3">
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> {{ __('messages.shipping.save_countries') }}</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const continentToggles = document.querySelectorAll('.continent-toggle');
    const allCountryChecks = document.querySelectorAll('.country-check');

    function updateContinentToggle(continent) {
        const toggle = document.getElementById('continent_' + continent);
        const checks = document.querySelectorAll('.country-' + continent);
        const total = checks.length;
        const checked = Array.from(checks).filter(c => c.checked).length;

        if (checked === 0) {
            toggle.checked = false;
            toggle.indeterminate = false;
        } else if (checked === total) {
            toggle.checked = true;
            toggle.indeterminate = false;
        } else {
            toggle.checked = false;
            toggle.indeterminate = true;
        }
    }

    function updateGlobalCounter() {
        const count = Array.from(allCountryChecks).filter(c => c.checked).length;
        document.getElementById('globalCounter').textContent = count + ' {{ __('messages.shipping.enabled_countries') }}';
    }

    continentToggles.forEach(function (toggle) {
        const continent = toggle.dataset.continent;
        updateContinentToggle(continent);

        toggle.addEventListener('change', function () {
            const checks = document.querySelectorAll('.country-' + continent);
            checks.forEach(c => c.checked = toggle.checked);
            updateGlobalCounter();
        });
    });

    allCountryChecks.forEach(function (check) {
        check.addEventListener('change', function () {
            updateContinentToggle(check.dataset.continent);
            updateGlobalCounter();
        });
    });
});
</script>
@endpush
@endsection
