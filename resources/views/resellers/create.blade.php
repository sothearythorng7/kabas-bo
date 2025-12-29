@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.resellers.title_create') }}</h1>

    <form action="{{ route('resellers.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">{{ __('messages.resellers.name') }}</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.resellers.type') }}</label>
            <select name="type" class="form-select" required>
                <option value="buyer">{{ __('messages.resellers.type_buyer') }}</option>
                <option value="consignment">{{ __('messages.resellers.type_consignment') }}</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> {{ __('messages.resellers.save') }}
        </button>
        <a href="{{ route('resellers.index') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> {{ __('messages.resellers.cancel') }}
        </a>
    </form>
</div>
@endsection
