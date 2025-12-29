@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.payment_method.title_create') }}</h1>
    @include('financial.layouts.nav')
    <form method="POST" action="{{ route('financial.payment-methods.store', $store->id) }}" class="mt-3">
        @csrf

        <div class="mb-3">
            <label class="form-label">{{ __('messages.payment_method.name') }}</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.payment_method.code') }}</label>
            <input type="text" name="code" class="form-control" required value="{{ old('code') }}">
        </div>

        <button type="submit" class="btn btn-success">{{ __('messages.payment_method.save') }}</button>
        <a href="{{ route('financial.payment-methods.index', $store->id) }}" class="btn btn-secondary">{{ __('messages.payment_method.cancel') }}</a>
    </form>
</div>
@endsection
