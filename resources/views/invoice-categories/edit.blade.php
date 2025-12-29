@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.invoice_category.title_edit') }}</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('invoice-categories.update', $invoiceCategory->id) }}" method="POST">
                @csrf
                @method('PUT')
                @if(request('store_id'))
                    <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                @endif
                @include('invoice-categories.form-fields', ['category' => $invoiceCategory])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> {{ __('messages.invoice_category.update') }}
                    </button>
                    <a href="{{ route('invoice-categories.index', request()->only('store_id')) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> {{ __('messages.invoice_category.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
