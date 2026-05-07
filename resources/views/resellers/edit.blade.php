@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.resellers.title_edit') }}</h1>

    <form action="{{ route('resellers.update', $reseller) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">{{ __('messages.resellers.name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ $reseller->name }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.resellers.type') }}</label>
            <select name="type" class="form-select" required>
                <option value="buyer" @selected($reseller->type=='buyer')>{{ __('messages.resellers.type_buyer') }}</option>
                <option value="consignment" @selected($reseller->type=='consignment')>{{ __('messages.resellers.type_consignment') }}</option>
            </select>
        </div>
        <div class="mb-3 form-check form-switch">
            <input type="checkbox" class="form-check-input" role="switch" id="is_active" name="is_active" value="1" @checked($reseller->is_active)>
            <label class="form-check-label" for="is_active">{{ __('messages.resellers.active') }}</label>
            <div class="form-text small">{{ __('messages.resellers.active_hint') }}</div>
        </div>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> {{ __('messages.resellers.update') }}
        </button>
        <a href="{{ route('resellers.index') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> {{ __('messages.resellers.cancel') }}
        </a>
    </form>
</div>
@endsection
