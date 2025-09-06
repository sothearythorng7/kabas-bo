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
            <label>Rôle</label>
            <select name="role" class="form-control" required>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                @endforeach
            </select>
        </div>

            <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
    </form>
</div>
@endsection
