@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.user_edit.btnCreate') }}</h1>
    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div class="mb-3">
            <label>{{ __('messages.user_edit.name') }}</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.email') }}</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.password') }}</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.password_confirmation') }}</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.role') }}</label>
            <select name="role" class="form-control" required>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.site') }} <span class="text-danger">*</span></label>
            <select name="store_id" class="form-control" id="store_id" required>
                <option value="">{{ __('messages.users_extra.select_site') }}</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.pin_code') }}</label>
            <input type="text" name="pin_code" class="form-control" maxlength="6" pattern="\d{6}" placeholder="000000">
            <small class="form-text text-muted">{{ __('messages.users_extra.pin_help') }}</small>
        </div>

        <!-- Staff Profile Options -->
        <div class="card mb-3">
            <div class="card-header">
                <strong>{{ __('messages.user.staff_profile') }}</strong>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="staff_mode" id="staff_mode_none" value="none" checked>
                        <label class="form-check-label" for="staff_mode_none">
                            {{ __('messages.user.no_staff_profile') }}
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="staff_mode" id="staff_mode_create" value="create">
                        <label class="form-check-label" for="staff_mode_create">
                            {{ __('messages.user.create_staff_profile') }}
                        </label>
                    </div>
                    @if(isset($unlinkedStaffMembers) && $unlinkedStaffMembers->count() > 0)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="staff_mode" id="staff_mode_link" value="link">
                        <label class="form-check-label" for="staff_mode_link">
                            {{ __('messages.user.link_existing_staff') }}
                        </label>
                    </div>
                    @endif
                </div>

                <!-- Create Staff Fields -->
                <div id="staff_create_fields" class="d-none border-top pt-3 mt-2">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>{{ __('messages.staff.phone') }}</label>
                                <input type="text" name="staff_phone" class="form-control" placeholder="+855...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>{{ __('messages.staff.hire_date') }}</label>
                                <input type="date" name="staff_hire_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>{{ __('messages.staff.base_salary') }}</label>
                                <input type="number" name="staff_base_salary" class="form-control" step="0.00001" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>{{ __('messages.staff.currency') }}</label>
                                <select name="staff_currency" class="form-control">
                                    <option value="USD">USD</option>
                                    <option value="KHR">KHR</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Link Staff Dropdown -->
                <div id="staff_link_fields" class="d-none border-top pt-3 mt-2">
                    <div class="mb-3">
                        <label for="staff_member_id" class="form-label">{{ __('messages.user.select_staff') }}</label>
                        <select class="form-select" id="staff_member_id" name="staff_member_id">
                            <option value="">-- {{ __('messages.user.select_staff') }} --</option>
                            @foreach($unlinkedStaffMembers ?? [] as $sm)
                                <option value="{{ $sm->id }}" {{ old('staff_member_id') == $sm->id ? 'selected' : '' }}>
                                    {{ $sm->name }} ({{ $sm->store?->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const staffModeRadios = document.querySelectorAll('input[name="staff_mode"]');
    const createFields = document.getElementById('staff_create_fields');
    const linkFields = document.getElementById('staff_link_fields');

    function updateStaffFields() {
        const selectedMode = document.querySelector('input[name="staff_mode"]:checked').value;

        createFields.classList.add('d-none');
        linkFields.classList.add('d-none');

        if (selectedMode === 'create') {
            createFields.classList.remove('d-none');
        } else if (selectedMode === 'link') {
            linkFields.classList.remove('d-none');
        }
    }

    staffModeRadios.forEach(radio => {
        radio.addEventListener('change', updateStaffFields);
    });

    updateStaffFields();
});
</script>
@endpush
