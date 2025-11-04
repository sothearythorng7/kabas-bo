@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Sales Reports Overview")</h1>

    {{-- Totaux --}}
    <div class="alert alert-info">
        <strong>@t("Total theoretical amount"):</strong>
        ${{ number_format($totalTheoretical, 2) }}
    </div>

    <div class="alert alert-warning">
        <strong>@t("Total unpaid invoiced reports"):</strong>
        ${{ number_format($totalUnpaidInvoiced, 2) }}
    </div>

    {{-- Onglets par statut --}}
    <ul class="nav nav-tabs" id="reportsTabs" role="tablist">
        @php
        $statuses = [
            'waiting_invoice' => @t("order.waiting_invoice"),
            'invoiced_unpaid' => @t("Invoice received - not paid"),
            'invoiced_paid' => @t("Invoice received - paid"),
        ];
        $badgeColors = [
            'waiting_invoice' => 'info',
            'invoiced_unpaid' => 'danger',
            'invoiced_paid' => 'success',
        ];
        @endphp

        @foreach($statuses as $key => $label)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                        id="{{ $key }}-tab" data-bs-toggle="tab" 
                        data-bs-target="#{{ $key }}" type="button" role="tab" 
                        aria-controls="{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    {{ $label }}
                    <span class="badge bg-{{ $badgeColors[$key] }}">
                        {{ $reportsByStatus[$key]->total() }}
                    </span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content mt-3" id="reportsTabsContent">
        @foreach($statuses as $key => $label)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                 id="{{ $key }}" role="tabpanel" aria-labelledby="{{ $key }}-tab">

                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>@t("Fournisseur")</th>
                            <th>@t("Store name")</th>
                            <th>@t("Période")</th>
                            <th>@t("Theoretical amount")</th>
                            @if(str_contains($key, 'invoiced'))
                                <th>@t("Total billed")</th>
                                <th>@t("Paid")</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportsByStatus[$key] as $report)
                            <tr>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('sale-reports.show', [$report->supplier, $report]) }}">
                                                <i class="bi bi-eye-fill"></i> @t("View")
                                            </a>
                                        </li>

                                        {{-- Invoice reception possible uniquement si waiting_invoice --}}
                                        @if($report->status === 'waiting_invoice')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('sale-reports.invoiceReception', [$report->supplier, $report]) }}">
                                                    <i class="bi bi-receipt"></i>@t("order.invoice_reception")
                                                </a>
                                            </li>
                                        @endif

                                        {{-- Mark as paid uniquement si invoiced et non payé --}}
                                        @if($report->status === 'invoiced' && !$report->is_paid)
                                            <li>
                                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#markAsPaidModal-{{ $report->id }}">
                                                    <i class="bi bi-cash-coin"></i> @t("Mark as paid")
                                                </button>
                                            </li>
                                        @endif

                                        {{-- Télécharger le PDF et envoyer par mail si dispo --}}
                                        @if($report->report_file_path)
                                            <li>
                                                <a class="dropdown-item" href="{{ Storage::url($report->report_file_path) }}" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf"></i> Télécharger le rapport
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('sale-reports.send', [$report->supplier, $report]) }}">
                                                    <i class="bi bi-envelope-fill"></i> Envoyer par mail
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                                <td>{{ $report->supplier->name }}</td>
                                <td>{{ $report->store->name }}</td>
                                <td>{{ $report->period_start->format('d/m/Y') }} - {{ $report->period_end->format('d/m/Y') }}</td>
                                <td>${{ number_format($report->total_amount_theoretical, 2) }}</td>

                                @if(str_contains($key, 'invoiced'))
                                    <td>${{ number_format($report->total_amount_invoiced, 2) }}</td>
                                    <td>
                                        @if($report->is_paid)
                                            <span class="badge bg-success">@t("Yes")</span>
                                        @else
                                            <span class="badge bg-danger">@t("No")</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>

                            {{-- Modal Mark as Paid --}}
                            @if($key === 'invoiced_unpaid')
                            <div class="modal fade" id="markAsPaidModal-{{ $report->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="{{ route('sale-reports.markAsPaid', [$report->supplier, $report]) }}" method="POST">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">@t("Mark report as paid")</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">@t("Amount paid")</label>
                                                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ $report->total_amount_invoiced }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">@t("Payment method")</label>
                                                    <select name="payment_method_id" class="form-select" required>
                                                        @foreach($paymentMethods as $method)
                                                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">@t("Payment reference")</label>
                                                    <input type="text" name="payment_reference" class="form-control">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                                <button type="submit" class="btn btn-success">@t("Confirm payment")</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>

                {{ $reportsByStatus[$key]->appends(request()->query())->withQueryString()->fragment($key)->links() }}
            </div>
        @endforeach
    </div>
</div>
@endsection
