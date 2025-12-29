@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.financial.journal_detail') }} – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <div class="card">
        <div class="card-body">
            <p><strong>{{ __('messages.financial.date') }} :</strong> {{ $journal->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>{{ __('messages.financial.transaction') }} :</strong> {{ $journal->transaction?->label ?? '—' }}</p>
            <p><strong>{{ __('messages.financial.user') }} :</strong> {{ $journal->user?->name }}</p>
            <p><strong>{{ __('messages.financial.action') }} :</strong> {{ ucfirst($journal->action) }}</p>

            <h5>{{ __('messages.financial.before_modification') }} :</h5>
            <pre>{{ json_encode($journal->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

            <h5>{{ __('messages.financial.after_modification') }} :</h5>
            <pre>{{ json_encode($journal->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('financial.journals.index', $store->id) }}" class="btn btn-secondary">{{ __('messages.financial.back') }}</a>
    </div>
</div>
@endsection
