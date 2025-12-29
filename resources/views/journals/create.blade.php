@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.journals.add_transaction') }} - {{ $site->name }}</h1>

    <form action="{{ route('stores.journals.store', $site) }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">{{ __('messages.common.date') }}</label>
            <input type="date" class="form-control" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.journals.account') }}</label>
            <select name="account_id" class="form-select" required>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }} ({{ ucfirst($account->type) }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.journals.type') }}</label>
            <select name="type" class="form-select" required>
                <option value="in">{{ __('messages.journals.type_in') }}</option>
                <option value="out">{{ __('messages.journals.type_out') }}</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.journals.amount') }}</label>
            <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('messages.common.description') }}</label>
            <textarea class="form-control" name="description">{{ old('description') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('messages.Ajouter') }}</button>
        <a href="{{ route('stores.journals.index', $site) }}" class="btn btn-secondary">{{ __('messages.Annuler') }}</a>
    </form>
</div>
@endsection
