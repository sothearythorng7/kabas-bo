@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title mb-0">{{ __('messages.staff.add_employee') }}</h1>
        <a href="{{ route('staff.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('staff.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">{{ __('messages.staff.personal_info') }}</h5>

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('messages.staff.name') }} *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('messages.staff.email') }}</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">{{ __('messages.staff.email_optional') }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">{{ __('messages.staff.phone') }}</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">{{ __('messages.staff.address') }}</label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      id="address" name="address" rows="2">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-3">{{ __('messages.staff.employment_info') }}</h5>

                        <div class="mb-3">
                            <label for="store_id" class="form-label">{{ __('messages.staff.store') }}</label>
                            <select class="form-select @error('store_id') is-invalid @enderror" id="store_id" name="store_id">
                                <option value="">{{ __('messages.staff.no_store') }}</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('store_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="hire_date" class="form-label">{{ __('messages.staff.hire_date') }}</label>
                            <input type="date" class="form-control @error('hire_date') is-invalid @enderror"
                                   id="hire_date" name="hire_date" value="{{ old('hire_date', date('Y-m-d')) }}">
                            @error('hire_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <h5 class="mb-3">{{ __('messages.staff.initial_salary') }}</h5>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="base_salary" class="form-label">{{ __('messages.staff.base_salary') }}</label>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('base_salary') is-invalid @enderror"
                                           id="base_salary" name="base_salary" value="{{ old('base_salary') }}">
                                    @error('base_salary')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('messages.staff.salary_optional') }}</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">{{ __('messages.staff.currency') }}</label>
                                    <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency">
                                        <option value="USD" {{ old('currency', 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                                        <option value="XOF" {{ old('currency') === 'XOF' ? 'selected' : '' }}>XOF</option>
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('staff.index') }}" class="btn btn-secondary me-2">
                        {{ __('messages.btn.cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> {{ __('messages.staff.add_employee') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
