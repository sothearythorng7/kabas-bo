@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.user_edit.title_edit') }}</h1>

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>{{ __('messages.user_edit.name') }}</label>
            <input type="text" name="name" value="{{ $user->name }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.email') }}</label>
            <input type="email" name="email" value="{{ $user->email }}" class="form-control" required>
        </div>
        
        <div class="mb-3">
            <label>{{ __('messages.user_edit.language') }}</label>
            <select name="locale" class="form-select">
                <option value="fr" {{ $user->locale === 'fr' ? 'selected' : '' }}>Français</option>
                <option value="en" {{ $user->locale === 'en' ? 'selected' : '' }}>English</option>
            </select>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.new_password') }}</label>
            <input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="{{ __('messages.user_edit.leave_blank_to_keep') }}">
            <small class="form-text text-muted">{{ __('messages.user_edit.leave_blank_if_no_change') }}</small>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.password_confirmation') }}</label>
            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.role') }}</label>
            <select name="role" class="form-control" required>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                        {{ ucfirst($role->name) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>{{ __('messages.user_edit.site') }} <span class="text-danger">*</span></label>
            <select name="store_id" class="form-control" id="store_id" required>
                <option value="">{{ __('messages.users_extra.select_site') }}</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ $user->store_id == $store->id ? 'selected' : '' }}>
                        {{ $store->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.pin_code') }}</label>
            <input type="text" name="pin_code" value="{{ $user->pin_code }}" class="form-control" maxlength="6" pattern="\d{6}" placeholder="000000">
            <small class="form-text text-muted">{{ __('messages.users_extra.pin_help') }}</small>
        </div>

        <div class="mb-3">
            <label for="staff_member_id" class="form-label">{{ __('messages.user.link_to_staff') }}</label>
            @if($user->staffMember)
                <div class="mb-2">
                    <a href="{{ route('staff.show', $user->staffMember) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person-vcard"></i> {{ __('messages.staff.view_staff_profile') }}: {{ $user->staffMember->name }}
                    </a>
                </div>
            @endif
            <select class="form-select" id="staff_member_id" name="staff_member_id">
                <option value="">{{ __('messages.user.no_staff_link') }}</option>
                @if($user->staffMember)
                    <option value="{{ $user->staffMember->id }}" selected>
                        {{ $user->staffMember->name }} ({{ $user->staffMember->store?->name }})
                    </option>
                @endif
                @foreach($unlinkedStaffMembers ?? [] as $sm)
                    <option value="{{ $sm->id }}">
                        {{ $sm->name }} ({{ $sm->store?->name }})
                    </option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
    </form>
</div>

@endsection
