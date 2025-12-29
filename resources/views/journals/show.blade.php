@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.journals.details_title') }} #{{ $journal->id }} - {{ $site->name }}</h1>

    <ul class="list-group">
        <li class="list-group-item"><strong>{{ __('messages.journals.date') }}:</strong> {{ $journal->date->format('d/m/Y') }}</li>
        <li class="list-group-item"><strong>{{ __('messages.journals.account') }}:</strong> {{ $journal->account->name }}</li>
        <li class="list-group-item"><strong>{{ __('messages.journals.type') }}:</strong> {{ ucfirst($journal->type) }}</li>
        <li class="list-group-item"><strong>{{ __('messages.journals.amount') }}:</strong> {{ number_format($journal->amount, 2, ',', ' ') }} $</li>
        <li class="list-group-item"><strong>{{ __('messages.journals.description') }}:</strong> {{ $journal->description }}</li>
    </ul>

    <a href="{{ route('stores.journals.index', $site) }}" class="btn btn-secondary mt-3">{{ __('messages.journals.back') }}</a>
</div>
@endsection
