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
                                    <input type="number" step="0.00001" min="0"
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

                {{-- Account User Section --}}
                <h5 class="mb-3">{{ __('messages.staff.account_section') }}</h5>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="account_mode" id="account_mode_none"
                               value="none" {{ old('account_mode', 'none') === 'none' ? 'checked' : '' }}>
                        <label class="form-check-label" for="account_mode_none">
                            <strong>{{ __('messages.staff.account_mode_none') }}</strong>
                            <br><small class="text-muted">{{ __('messages.staff.account_mode_none_desc') }}</small>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="account_mode" id="account_mode_create"
                               value="create" {{ old('account_mode') === 'create' ? 'checked' : '' }}>
                        <label class="form-check-label" for="account_mode_create">
                            <strong>{{ __('messages.staff.account_mode_create') }}</strong>
                            <br><small class="text-muted">{{ __('messages.staff.account_mode_create_desc') }}</small>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="account_mode" id="account_mode_link"
                               value="link" {{ old('account_mode') === 'link' ? 'checked' : '' }}>
                        <label class="form-check-label" for="account_mode_link">
                            <strong>{{ __('messages.staff.account_mode_link') }}</strong>
                            <br><small class="text-muted">{{ __('messages.staff.account_mode_link_desc') }}</small>
                        </label>
                    </div>
                </div>

                {{-- Create Account Fields --}}
                <div id="account-create-section" style="display: {{ old('account_mode') === 'create' ? 'block' : 'none' }};">
                    <div class="card card-body bg-light mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_email" class="form-label">{{ __('messages.staff.email') }} ({{ __('messages.user_edit.email') }}) *</label>
                                    <input type="email" class="form-control @error('user_email') is-invalid @enderror"
                                           id="user_email" name="user_email" value="{{ old('user_email') }}">
                                    @error('user_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_password" class="form-label">{{ __('messages.staff.password') }} *</label>
                                    <input type="password" class="form-control @error('user_password') is-invalid @enderror"
                                           id="user_password" name="user_password">
                                    @error('user_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="pin_code" class="form-label">{{ __('messages.staff.pin_code') }}</label>
                                    <input type="text" class="form-control @error('pin_code') is-invalid @enderror"
                                           id="pin_code" name="pin_code" value="{{ old('pin_code') }}"
                                           maxlength="6" pattern="\d{6}" placeholder="000000">
                                    @error('pin_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="user_role" class="form-label">{{ __('messages.staff.role') }} *</label>
                                    <select class="form-select @error('user_role') is-invalid @enderror" id="user_role" name="user_role">
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}" {{ old('user_role') === $role->name ? 'selected' : '' }}>
                                                {{ ucfirst($role->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="locale" class="form-label">{{ __('messages.staff.locale') }}</label>
                                    <select class="form-select @error('locale') is-invalid @enderror" id="locale" name="locale">
                                        <option value="fr" {{ old('locale', 'fr') === 'fr' ? 'selected' : '' }}>Fran&ccedil;ais</option>
                                        <option value="en" {{ old('locale') === 'en' ? 'selected' : '' }}>English</option>
                                    </select>
                                    @error('locale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Link Account Fields --}}
                <div id="account-link-section" style="display: {{ old('account_mode') === 'link' ? 'block' : 'none' }};">
                    <div class="card card-body bg-light mb-3">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">{{ __('messages.staff.select_existing_user') }} *</label>
                            <select class="form-select @error('user_id') is-invalid @enderror"
                                    id="user_id" name="user_id">
                                <option value="">-- {{ __('messages.staff.select_existing_user') }} --</option>
                                @foreach($unlinkedUsers as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="account_mode"]');
    const createSection = document.getElementById('account-create-section');
    const linkSection = document.getElementById('account-link-section');

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            createSection.style.display = this.value === 'create' ? 'block' : 'none';
            linkSection.style.display = this.value === 'link' ? 'block' : 'none';
        });
    });
});
</script>
@endpush

@endsection
