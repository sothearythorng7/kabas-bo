@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="crud_title">{{ __('messages.warehouse_invoices.edit_invoice', ['creditor' => $invoice->creditor_name]) }}</h1>
        <span class="badge 
            @switch($invoice->status->value)
                @case('to_pay') bg-primary @break
                @case('paid') bg-success @break
                @case('reimbursed') bg-warning text-dark @break
                @case('cancelled') bg-danger @break
                @default bg-secondary
            @endswitch
            fs-5 py-2 px-3">
            {{ $invoice->status->label() }}
        </span>
    </div>
    <a href="{{ route('warehouse-invoices.index') }}" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back_to_list') }}
    </a>
    <ul class="nav nav-tabs mb-3" id="invoiceTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">
                <i class="bi bi-list-check"></i> {{ __('messages.warehouse_invoices.general_information') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="files-tab" data-bs-toggle="tab" href="#files" role="tab">
                <i class="bi bi-card-image"></i> {{ __('messages.warehouse_invoices.files') }} <span class="badge bg-secondary">{{ $invoice->files->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history" role="tab">
                <i class="bi bi-clock-history"></i> {{ __('messages.warehouse_invoices.history') }} <span class="badge bg-secondary">{{ $invoice->histories->count() }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Onglet Général -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <form action="{{ route('warehouse-invoices.update', $invoice) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Montants et Statut --}}
                <div class="card mb-3">
                    <div class="card-header">{{ __('messages.warehouse_invoices.amount_usd') }} & {{ __('messages.warehouse_invoices.status') }}</div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.warehouse_invoices.status') }}</label>
                                <select name="status" class="form-select">
                                    @foreach(\App\Enums\InvoiceStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected($invoice->status === $status->value)>
                                            {{ $status->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.warehouse_invoices.type') }}</label>
                                <select name="type" class="form-select">
                                    @foreach(\App\Enums\InvoiceType::cases() as $type)
                                        <option value="{{ $type->value }}" @selected($invoice->type === $type->value)>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.warehouse_invoices.amount_usd') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" name="amount_usd" class="form-control" value="{{ old('amount_usd', $invoice->amount_usd) }}">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.warehouse_invoices.amount_riel') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">៛</span>
                                    <input type="number" step="1" name="amount_riel" class="form-control" value="{{ old('amount_riel', $invoice->amount_riel) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Informations générales --}}
                <div class="card mb-3">
                    <div class="card-header">{{ __('messages.warehouse_invoices.general_information') }}</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.warehouse_invoices.creditor') }}</label>
                            <input type="text" name="creditor_name" class="form-control" value="{{ old('creditor_name', $invoice->creditor_name) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.warehouse_invoices.description') }}</label>
                            <textarea name="description" class="form-control">{{ old('description', $invoice->description) }}</textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.warehouse_invoices.invoice_number') }}</label>
                                <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $invoice->invoice_number) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.warehouse_invoices.payment_number') }}</label>
                                <input type="text" name="payment_number" class="form-control" value="{{ old('payment_number', $invoice->payment_number) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.warehouse_invoices.payment_type') }}</label>
                            <select name="payment_type" class="form-select">
                                @foreach(\App\Enums\PaymentType::cases() as $paymentType)
                                    <option value="{{ $paymentType->value }}" @selected($invoice->payment_type === $paymentType->value)>
                                        {{ $paymentType->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success"><i class="bi bi-floppy-fill"></i>  {{ __('messages.warehouse_invoices.save') }}</button>
            </form>
        </div>

        <!-- Onglet Fichiers -->
        <div class="tab-pane fade" id="files" role="tabpanel">
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                {{ __('messages.warehouse_invoices.add_file') }}
            </button>

            <ul class="list-group">
                @foreach($invoice->files as $file)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ Storage::url($file->path) }}" target="_blank">{{ $file->label ?? basename($file->path) }}</a>
                        <form action="{{ route('warehouse-invoices.delete-file', [$invoice, $file->id]) }}" method="POST" onsubmit="return confirm('{{ __('messages.warehouse_invoices.delete_file_confirm') }}');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">{{ __('messages.warehouse_invoices.save') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Modale d'ajout de fichier -->
        <div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('warehouse-invoices.upload-files', $invoice) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadFileModalLabel">{{ __('messages.warehouse_invoices.add_file') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.warehouse_invoices.cancel') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.warehouse_invoices.file_input') }}</label>
                                <input type="file" name="files[]" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.warehouse_invoices.file_label') }}</label>
                                <input type="text" name="labels[]" class="form-control" placeholder="Ex: Facture fournisseur X">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.warehouse_invoices.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('messages.warehouse_invoices.add') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Onglet Historique -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>{{ __('messages.warehouse_invoices.user') }}</th>
                        <th>{{ __('messages.warehouse_invoices.date') }}</th>
                        <th>{{ __('messages.warehouse_invoices.changes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->histories as $h)
                        <tr>
                            <td>{{ $h->user?->name ?? __('messages.warehouse_invoices.not_available') }}</td>
                            <td>{{ $h->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <ul>
                                    @foreach($h->changes as $field => $change)
                                        @if(is_array($change) && isset($change['old'], $change['new']))
                                            <li><strong>{{ $field }}:</strong> "{{ $change['old'] }}" → "{{ $change['new'] }}"</li>
                                        @endif
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const usdInput = document.querySelector('input[name="amount_usd"]');
        const rielInput = document.querySelector('input[name="amount_riel"]');
        const rate = 4000;

        // Conversion USD → Riel
        usdInput.addEventListener('input', function() {
            const usd = parseFloat(this.value);
            if(!isNaN(usd)) {
                rielInput.value = Math.round(usd * rate);
            } else {
                rielInput.value = '';
            }
        });

        // Conversion Riel → USD
        rielInput.addEventListener('input', function() {
            const riel = parseFloat(this.value);
            if(!isNaN(riel)) {
                usdInput.value = (riel / rate).toFixed(2);
            } else {
                usdInput.value = '';
            }
        });
    });
</script>
@endpush

