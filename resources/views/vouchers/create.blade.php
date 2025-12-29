@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">{{ __('messages.voucher.create') }}</h1>
        <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('messages.voucher.manual_create') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('vouchers.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="amount" class="form-label">{{ __('messages.voucher.amount') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" max="10000" name="amount" id="amount"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    value="{{ old('amount') }}" required>
                                <span class="input-group-text">$</span>
                            </div>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="store_id" class="form-label">{{ __('messages.store.name') }}</label>
                            <select name="store_id" id="store_id" class="form-select @error('store_id') is-invalid @enderror">
                                <option value="">{{ __('messages.select_optional') }}</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('store_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('messages.voucher.store_help') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="validity" class="form-label">{{ __('messages.voucher.validity') }} <span class="text-danger">*</span></label>
                            <select name="validity" id="validity" class="form-select @error('validity') is-invalid @enderror" required>
                                <option value="1_month" {{ old('validity') == '1_month' ? 'selected' : '' }}>{{ __('messages.voucher.validity_options.1_month') }}</option>
                                <option value="3_months" {{ old('validity') == '3_months' ? 'selected' : '' }}>{{ __('messages.voucher.validity_options.3_months') }}</option>
                                <option value="6_months" {{ old('validity', '6_months') == '6_months' ? 'selected' : '' }}>{{ __('messages.voucher.validity_options.6_months') }}</option>
                                <option value="1_year" {{ old('validity') == '1_year' ? 'selected' : '' }}>{{ __('messages.voucher.validity_options.1_year') }}</option>
                                <option value="5_years" {{ old('validity') == '5_years' ? 'selected' : '' }}>{{ __('messages.voucher.validity_options.5_years') }}</option>
                            </select>
                            @error('validity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> {{ __('messages.voucher.btnCreate') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('messages.voucher.info') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            {{ __('messages.voucher.info_code_format') }}
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            {{ __('messages.voucher.info_validity') }}
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            {{ __('messages.voucher.info_single_use') }}
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            {{ __('messages.voucher.info_all_stores') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
