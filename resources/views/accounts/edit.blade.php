@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ isset($account) ? __('messages.accounts.edit') : __('messages.accounts.create') }} - {{ $site->name }}</h1>

    <form action="{{ isset($account) ? route('stores.accounts.update', [$site, $account]) : route('stores.accounts.store', $site) }}" method="POST">
        @csrf
        @if(isset($account))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label for="name" class="form-label">{{ __('messages.common.name') }}</label>
            <input type="text" class="form-control" name="name" value="{{ $account->name ?? old('name') }}" required>
        </div>
        <div class="mb-3">
            <label for="type" class="form-label">{{ __('messages.Type') }}</label>
            <select class="form-select" name="type" required>
                <option value="cash" {{ (isset($account) && $account->type=='cash') ? 'selected' : '' }}>{{ __('messages.accounts.type_cash') }}</option>
                <option value="bank" {{ (isset($account) && $account->type=='bank') ? 'selected' : '' }}>{{ __('messages.accounts.type_bank') }}</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="balance" class="form-label">{{ __('messages.accounts.initial_balance') }}</label>
            <input type="number" step="0.01" class="form-control" name="balance" value="{{ $account->balance ?? old('balance') }}" required>
        </div>

        <button type="submit" class="btn btn-primary">{{ isset($account) ? __('messages.Mettre à jour') : __('messages.Créer') }}</button>
        <a href="{{ route('stores.accounts.index', $site) }}" class="btn btn-secondary">{{ __('messages.Annuler') }}</a>
    </form>
</div>
@endsection
