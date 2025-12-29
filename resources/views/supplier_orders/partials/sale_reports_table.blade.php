<table class="table table-striped table-hover">
<thead>
<tr>
    <th></th>
    <th>{{ __('messages.supplier.name') }}</th>
    <th>{{ __('messages.sale_report.created_at') }}</th>
    <th>Destination</th>
    @if(in_array($key, ['waiting_invoice','received_unpaid','received_paid']))
        <th>{{ __('messages.Total ordered') }}</th>
        <th>{{ __('messages.Total received') }}</th>
    @endif
    <th>{{ __('messages.Theoretical amount') }}</th>
    @if(in_array($key, ['received_unpaid','received_paid']))
        <th>{{ __('messages.Total billed') }}</th>
        <th>{{ __('messages.Paid') }}</th>
    @endif
</tr>
</thead>
<tbody>
@foreach($reports as $report)
<tr>
    <td>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="{{ route('sale-reports.show', [$report->supplier, $report]) }}">
                        <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                    </a>
                </li>
                {{-- Actions Sale Reports selon statut --}}
                @include('supplier_orders.partials.sale_report_actions', ['report' => $report])
            </ul>
        </div>
    </td>

    <td>{{ $report->supplier->name }}</td>
    <td>{{ $report->created_at->format('d/m/Y') }}</td>
    <td>{{ $report->destinationStore?->name ?? '-' }}</td>

    @if(in_array($key, ['waiting_invoice','received_unpaid','received_paid']))
        <td>{{ $report->totalOrdered }}</td>
        <td>{{ $report->totalReceived }}</td>
    @endif

    <td>${{ number_format($report->totalAmount, 2) }}</td>

    @if(in_array($key, ['received_unpaid','received_paid']))
        <td>${{ number_format($report->totalInvoiced, 2) }}</td>
        <td>
            @if($report->is_paid)
                <span class="badge bg-success">{{ __('messages.Yes') }}</span>
            @else
                <span class="badge bg-danger">{{ __('messages.No') }}</span>
            @endif
        </td>
    @endif
</tr>

{{-- Modal Mark as Paid pour sale reports --}}
@if(in_array($key, ['received_unpaid','received_paid']) && !$report->is_paid)
<div class="modal fade" id="markAsPaidModal-{{ $report->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('sale-reports.markAsPaid', [$report->supplier, $report]) }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('messages.Mark sale report as paid') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">{{ __('messages.Amount paid') }}</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="{{ $report->totalInvoiced }}">
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
                <input type="text" name="payment_reference" class="form-control">
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

@endforeach
</tbody>
</table>

{{-- Pagination --}}
{{ $reports->appends(request()->query())->withQueryString()->fragment($key)->links() }}
