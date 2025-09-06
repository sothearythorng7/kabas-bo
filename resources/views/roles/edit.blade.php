@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.roles.title_edit') }}</h1>

    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>{{ __('messages.roles.name') }}</label>
            <input type="text" name="name" value="{{ $role->name }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.roles.permissions') }}</label>
            <div class="form-check">
                @foreach($permissions as $permission)
                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                           class="form-check-input" id="perm_{{ $permission->id }}"
                           {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                    <label for="perm_{{ $permission->id }}" class="form-check-label">{{ $permission->name }}</label><br>
                @endforeach
            </div>
        </div>

        <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
        <a href="{{ route('roles.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
    </form>
</div>
@endsection
