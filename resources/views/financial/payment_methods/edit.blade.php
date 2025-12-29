@extends('layouts.app')

@section('content')
<div class="container mt-4">
<h1 class="crud_title">{{ __('messages.payment_method.title_edit') }}</h1>
@include('financial.layouts.nav')
    <form method="POST" action="{{ route('financial.payment-methods.update', [$store->id, $paymentMethod->id]) }}" class="mt-3">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">{{ __('messages.payment_method.name') }}</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $paymentMethod->name) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.payment_method.code') }}</label>
            <input type="text" name="code" class="form-control" required value="{{ old('code', $paymentMethod->code) }}">
        </div>

        <button type="submit" class="btn btn-success">{{ __('messages.payment_method.update') }}</button>
        <a href="{{ route('financial.payment-methods.index', $store->id) }}" class="btn btn-secondary">{{ __('messages.payment_method.cancel') }}</a>
    </form>
</div>
@endsection
