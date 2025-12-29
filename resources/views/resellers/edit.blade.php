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
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> {{ __('messages.resellers.update') }}
        </button>
        <a href="{{ route('resellers.index') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> {{ __('messages.resellers.cancel') }}
        </a>
    </form>
</div>
@endsection
