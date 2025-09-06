@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.warehouse_invoices.new_invoice') }}</h1>

    <form action="{{ route('warehouse-invoices.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label class="form-label">{{ __('messages.warehouse_invoices.creditor') }}</label>
            <input type="text" name="creditor_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.warehouse_invoices.description') }}</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.warehouse_invoices.type') }}</label>
            <select name="type" class="form-select" required>
                @foreach(\App\Enums\InvoiceType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col mb-3">
                <label class="form-label">{{ __('messages.warehouse_invoices.amount_usd') }}</label>
                <input type="number" step="0.01" name="amount_usd" class="form-control">
            </div>
            <div class="col mb-3">
                <label class="form-label">{{ __('messages.warehouse_invoices.amount_riel') }}</label>
                <input type="number" step="1" name="amount_riel" class="form-control">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.warehouse_invoices.creditor_invoice_number') }}</label>
            <input type="text" name="creditor_invoice_number" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.warehouse_invoices.internal_payment_number') }}</label>
            <input type="text" name="internal_payment_number" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.warehouse_invoices.payment_type') }}</label>
            <select name="payment_type" class="form-select" required>
                @foreach(\App\Enums\PaymentType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.warehouse_invoices.attachment') }}</label>
            <input type="file" name="attachment" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">{{ __('messages.warehouse_invoices.save') }}</button>
    </form>
</div>
@endsection
