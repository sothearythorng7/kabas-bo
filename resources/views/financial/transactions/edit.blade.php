@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.financial.edit_transaction') }} – {{ $store->name }}</h1>
    @include('financial.layouts.nav')

    <form method="POST" action="{{ route('financial.transactions.update', [$store->id, $transaction->id]) }}" enctype="multipart/form-data" class="mt-3">
        @csrf
        @method('PUT')

        <!-- Première ligne : Date et Montant -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.financial.date') }}</label>
                <input type="date" name="transaction_date" class="form-control"
                       value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.financial.amount') }}</label>
                <input type="number" step="0.01" name="amount" class="form-control"
                       value="{{ old('amount', $transaction->amount) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.financial.currency') }}</label>
                <select name="currency" class="form-select">
                    <option value="USD" @selected($transaction->currency === 'USD')>USD</option>
                </select>
            </div>
        </div>

        <!-- Deuxième ligne : Compte et Méthode de paiement -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.financial.account') }}</label>
                <select name="account_id" class="form-select" required>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" @selected($transaction->account_id == $acc->id)>
                            {{ $acc->code }} - {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.financial.payment_method') }}</label>
                <select name="payment_method_id" class="form-select" required>
                    @foreach($methods as $m)
                        <option value="{{ $m->id }}" @selected($transaction->payment_method_id == $m->id)>
                            {{ $m->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Troisième ligne : Type (radio) -->
        <div class="mb-3">
            <label class="form-label">{{ __('messages.financial.type') }}</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="direction" value="debit"
                       @checked($transaction->direction === 'debit')>
                <label class="form-check-label">{{ __('messages.financial.debit') }}</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="direction" value="credit"
                       @checked($transaction->direction === 'credit')>
                <label class="form-check-label">{{ __('messages.financial.credit') }}</label>
            </div>
        </div>

        <!-- Libellé et Description -->
        <div class="mb-3">
            <label class="form-label">{{ __('messages.financial.label') }}</label>
            <input type="text" name="label" class="form-control"
                   value="{{ old('label', $transaction->label) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.financial.description') }}</label>
            <textarea name="description" class="form-control">{{ old('description', $transaction->description) }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.financial.add_attachments') }}</label>
            <input type="file" name="attachments[]" multiple class="form-control">

            @if($transaction->attachments->count())
                <h6 class="mt-3">{{ __('messages.financial.existing_attachments') }}</h6>
                <ul class="mt-2">
                    @foreach($transaction->attachments as $file)
                        <li class="d-flex align-items-center justify-content-between">
                            <a href="{{ $file->url }}" target="_blank">{{ $file->filename }}</a>
                            <div>
                                <input type="checkbox" name="delete_attachments[]" value="{{ $file->id }}">
                                <label class="form-check-label text-danger">{{ __('messages.financial.delete') }}</label>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <!-- Boutons -->
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-success">{{ __('messages.financial.update') }}</button>
            <a href="{{ route('financial.transactions.index', $store->id) }}" class="btn btn-secondary">{{ __('messages.financial.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
