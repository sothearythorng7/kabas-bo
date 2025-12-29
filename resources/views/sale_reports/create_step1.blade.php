@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.sale_report.create_title') }} {{ $supplier->name }}</h1>
    <p class="text-muted">{{ __('messages.sale_report.step1_description') }}</p>

    <form action="{{ route('sale-reports.create.step2', $supplier) }}" method="GET">
        {{-- Sélection du Store --}}
        <div class="mb-3">
            <label for="store_id" class="form-label">{{ __('messages.store.name') }}</label>
            <select name="store_id" id="store_id" class="form-select @error('store_id') is-invalid @enderror" required>
                <option value="">-- {{ __('messages.sale_report.select_store') }} --</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}"
                            data-last-report="{{ $lastReportsByStore[$store->id] ?? '' }}"
                            {{ old('store_id', request('store_id')) == $store->id ? 'selected' : '' }}>
                        {{ $store->name }}
                    </option>
                @endforeach
            </select>
            @error('store_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            {{-- Date de début de période --}}
            <div class="col-md-6 mb-3">
                <label for="period_start" class="form-label">{{ __('messages.sale_report.period_start') }}</label>
                <input type="date" class="form-control @error('period_start') is-invalid @enderror" name="period_start" id="period_start" value="{{ old('period_start', request('period_start', now()->format('Y-m-d'))) }}" required>
                <small class="text-muted" id="period_start_hint"></small>
                @error('period_start')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Date de fin (date du rapport) --}}
            <div class="col-md-6 mb-3">
                <label for="period_end" class="form-label">{{ __('messages.sale_report.period_end') }}</label>
                <input type="date" class="form-control @error('period_end') is-invalid @enderror" name="period_end" id="period_end" value="{{ old('period_end', request('period_end', now()->format('Y-m-d'))) }}" required>
                @error('period_end')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Boutons --}}
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-arrow-right"></i> {{ __('messages.sale_report.next_step') }}
            </button>
            <a href="{{ route('suppliers.edit', $supplier) }}#sales-reports" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const storeSelect = document.getElementById('store_id');
    const periodStartInput = document.getElementById('period_start');
    const periodStartHint = document.getElementById('period_start_hint');

    function updatePeriodStart() {
        const selectedOption = storeSelect.options[storeSelect.selectedIndex];
        const lastReportDate = selectedOption.getAttribute('data-last-report');

        if (lastReportDate) {
            periodStartInput.value = lastReportDate;
            periodStartHint.textContent = '{{ __('messages.sale_report.last_report_date') }}';
        } else {
            periodStartHint.textContent = '{{ __('messages.sale_report.no_previous_report') }}';
        }
    }

    storeSelect.addEventListener('change', updatePeriodStart);

    // Initialize on page load if a store is already selected
    if (storeSelect.value) {
        updatePeriodStart();
    }
});
</script>
@endsection
