@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.store.details') }}</h1>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ $store->name }}</h5>
            <p class="card-text"><strong>{{ __('messages.store.address') }}:</strong> {{ $store->address }}</p>
            <p class="card-text"><strong>{{ __('messages.store.phone') }}:</strong> {{ $store->phone }}</p>
            <p class="card-text"><strong>{{ __('messages.store.email') }}:</strong> {{ $store->email }}</p>
            <p class="card-text"><strong>{{ __('messages.store.opening_time') }}:</strong> {{ $store->opening_time ?? '-' }}</p>
            <p class="card-text"><strong>{{ __('messages.store.closing_time') }}:</strong> {{ $store->closing_time ?? '-' }}</p>
        </div>
    </div>
    <a href="{{ route('stores.index') }}" class="btn btn-secondary mt-3">{{ __('messages.btn.back_to_list') }}</a>
</div>
@endsection
