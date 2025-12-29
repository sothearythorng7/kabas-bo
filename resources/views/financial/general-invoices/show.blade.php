@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.general_invoices.invoice_details') }}</h1>
    @include('financial.layouts.nav')

    {{-- Actions --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="{{ route('financial.general-invoices.edit', [$store->id, $generalInvoice->id]) }}" class="btn btn-warning">
                    <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                </a>

                @if($generalInvoice->status === 'pending')
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#markAsPaidModal">
                        <i class="bi bi-cash-stack"></i> {{ __('messages.general_invoices.mark_as_paid') }}
                    </button>
                @endif

                @if($generalInvoice->attachment)
                    <a href="{{ route('financial.general-invoices.attachment', [$store->id, $generalInvoice->id]) }}" target="_blank" class="btn btn-dark">
                        <i class="bi bi-download"></i> {{ __('messages.general_invoices.download') }}
                    </a>
                @endif

                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p><strong>{{ __('messages.general_invoices.label') }}:</strong> {{ $generalInvoice->label }}</p>
            <p><strong>{{ __('messages.general_invoices.note') }}:</strong> {{ $generalInvoice->note ?? '-' }}</p>
            <p><strong>{{ __('messages.general_invoices.amount') }}:</strong> {{ number_format($generalInvoice->amount, 2) }} $</p>
            <p><strong>{{ __('messages.general_invoices.due_date') }}:</strong> {{ $generalInvoice->due_date?->format('d/m/Y') ?? '-' }}</p>
            <p><strong>{{ __('messages.general_invoices.account') }}:</strong> {{ $generalInvoice->account->name }}</p>
            @if($generalInvoice->category)
                <p><strong>{{ __('messages.general_invoices.category') }}:</strong>
                    <span class="badge" style="background-color: {{ $generalInvoice->category->color }}">
                        {{ $generalInvoice->category->name }}
                    </span>
                </p>
            @endif
            <p><strong>{{ __('messages.blog_post.status') }}:</strong>
                @if($generalInvoice->status === 'paid')
                    <span class="badge bg-success">{{ __('messages.general_invoices.status_paid') }}</span>
                @else
                    <span class="badge bg-warning">{{ __('messages.general_invoices.status_pending') }}</span>
                @endif
            </p>
            @if($generalInvoice->payment_date)
                <p><strong>{{ __('messages.general_invoices.payment_date') }}:</strong>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle"></i> {{ $generalInvoice->payment_date->format('d/m/Y') }}
                    </span>
                </p>
            @endif
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('financial.general-invoices.index', $store->id) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>
</div>

{{-- Modal Mark as Paid --}}
@if($generalInvoice->status === 'pending')
<div class="modal fade" id="markAsPaidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <form action="{{ route('financial.general-invoices.mark-as-paid', [$store->id, $generalInvoice->id]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.general_invoices.mark_as_paid') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">
                            {{ __('messages.Amount paid') }} : <strong>{{ number_format($generalInvoice->amount, 2) }} $</strong>
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.Méthode de paiement') }}</label>
                        <select name="payment_method_id" class="form-select" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.Payment reference') }}</label>
                        <input type="text" name="payment_reference" class="form-control" placeholder="{{ __('messages.optional') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('messages.Confirm payment') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Modal Delete Confirmation --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ __('messages.general_invoices.delete_confirmation_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($generalInvoice->status === 'paid')
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>{{ __('messages.general_invoices.delete_paid_warning_title') }}</strong>
                    </div>
                    <p>{{ __('messages.general_invoices.delete_paid_warning_message') }}</p>
                @else
                    <p>{{ __('messages.general_invoices.delete_confirmation_message') }}</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('messages.btn.cancel') }}
                </button>
                <form action="{{ route('financial.general-invoices.destroy', [$store->id, $generalInvoice->id]) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> {{ __('messages.general_invoices.confirm_delete_btn') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
