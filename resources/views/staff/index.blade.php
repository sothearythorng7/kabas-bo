@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title mb-0">{{ __('messages.staff.title') }}</h1>
        <div>
            @if($pendingPaymentsCount > 0)
                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#bulkPaymentModal">
                    <i class="bi bi-cash-stack"></i> {{ __('messages.staff.pay_all') }}
                    <span class="badge bg-light text-success">{{ $pendingPaymentsCount }}</span>
                </button>
            @endif
            <a href="{{ route('staff.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> {{ __('messages.staff.add_employee') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Bandeau mois en cours --}}
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
        <div>
            <i class="bi bi-calendar-month"></i>
            <strong>{{ __('messages.staff.payroll_period') }}:</strong>
            {{ \Carbon\Carbon::parse($currentMonth . '-01')->translatedFormat('F Y') }}
        </div>
        <div>
            <span class="badge bg-warning text-dark fs-6">
                {{ $pendingPaymentsCount }} {{ __('messages.staff.pending_payments') }}
            </span>
        </div>
    </div>

    {{-- Filtres --}}
    <form action="{{ route('staff.index') }}" method="GET" class="row g-2 mb-4">
        <div class="col-md-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                   placeholder="{{ __('messages.staff.search_placeholder') }}">
        </div>
        <div class="col-md-2">
            <select name="store_id" class="form-select">
                <option value="">{{ __('messages.staff.all_stores') }}</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                        {{ $store->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="active" {{ $contractStatus === 'active' ? 'selected' : '' }}>
                    {{ __('messages.staff.contract_status.active') }}
                </option>
                <option value="terminated" {{ $contractStatus === 'terminated' ? 'selected' : '' }}>
                    {{ __('messages.staff.contract_status.terminated') }}
                </option>
                <option value="all" {{ $contractStatus === 'all' ? 'selected' : '' }}>
                    {{ __('messages.staff.all_status') }}
                </option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search"></i> {{ __('messages.btn.search') }}
            </button>
        </div>
        @if(request('q') || request('store_id') || request('status'))
        <div class="col-md-2">
            <a href="{{ route('staff.index') }}" class="btn btn-secondary w-100">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.reset') }}
            </a>
        </div>
        @endif
    </form>

    <form action="{{ route('staff.bulk-payment') }}" method="POST" id="bulkPaymentForm">
        @csrf
        <input type="hidden" name="store_id" id="bulk_store_id" value="">

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 30px;">
                            <input type="checkbox" class="form-check-input" id="selectAll" title="{{ __('messages.staff.select_all') }}">
                        </th>
                        <th></th>
                        <th>{{ __('messages.staff.name') }}</th>
                        <th>{{ __('messages.staff.store') }}</th>
                        <th class="text-center">{{ __('messages.staff.salary') }}</th>
                        <th class="text-center">{{ __('messages.staff.deductions') }}</th>
                        <th class="text-center">{{ __('messages.staff.net_to_pay') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    @php
                        $payroll = $user->payroll_calculated;
                        $canPay = $payroll['base_salary'] > 0 && !$payroll['is_paid'];
                    @endphp
                    <tr class="{{ $payroll['is_paid'] ? 'table-success' : '' }}">
                        <td>
                            @if($canPay)
                                <input type="checkbox" class="form-check-input user-checkbox"
                                       name="user_ids[]" value="{{ $user->id }}"
                                       data-amount="{{ $payroll['net_amount'] }}">
                            @endif
                        </td>
                        <td style="width: 1%; white-space: nowrap;">
                            <a href="{{ route('staff.show', $user) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            @if($user->roles->isNotEmpty())
                                <br><small class="text-muted">{{ $user->roles->pluck('name')->join(', ') }}</small>
                            @endif
                        </td>
                        <td>{{ $user->store->name ?? '-' }}</td>
                        <td class="text-center">
                            @if($payroll['base_salary'] > 0)
                                {{ number_format($payroll['base_salary'], 2) }} {{ $payroll['currency'] }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($payroll['deductions'] > 0)
                                <span class="text-danger">- {{ number_format($payroll['deductions'], 2) }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($payroll['is_paid'])
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> {{ __('messages.staff.paid') }}
                                </span>
                            @elseif($payroll['base_salary'] > 0)
                                <strong class="{{ $payroll['net_amount'] >= 0 ? 'text-primary' : 'text-danger' }}">
                                    {{ number_format($payroll['net_amount'], 2) }} {{ $payroll['currency'] }}
                                </strong>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-muted text-center">{{ __('messages.staff.no_staff') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{ $users->appends(request()->query())->links() }}
</div>

{{-- Modal paiement en masse --}}
<div class="modal fade" id="bulkPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-cash-stack"></i> {{ __('messages.staff.bulk_payment') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong id="selectedCount">0</strong> {{ __('messages.staff.employees_selected') }}
                    <br>
                    {{ __('messages.staff.total') }}: <strong id="selectedTotal">0.00</strong>
                </div>

                <div class="mb-3">
                    <label for="modal_store_id" class="form-label">{{ __('messages.staff.payment_from_store') }} *</label>
                    <select class="form-select" id="modal_store_id" required>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                <button type="button" class="btn btn-success" id="confirmBulkPayment">
                    <i class="bi bi-check-circle"></i> {{ __('messages.staff.confirm_bulk_payment') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const selectedTotal = document.getElementById('selectedTotal');
    const modalStoreId = document.getElementById('modal_store_id');
    const bulkStoreId = document.getElementById('bulk_store_id');
    const confirmBtn = document.getElementById('confirmBulkPayment');
    const form = document.getElementById('bulkPaymentForm');

    function updateTotals() {
        let count = 0;
        let total = 0;

        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                count++;
                total += parseFloat(cb.dataset.amount) || 0;
            }
        });

        selectedCount.textContent = count;
        selectedTotal.textContent = total.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(function(cb) {
            cb.checked = selectAll.checked;
        });
        updateTotals();
    });

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateTotals);
    });

    confirmBtn.addEventListener('click', function() {
        const count = document.querySelectorAll('.user-checkbox:checked').length;
        if (count === 0) {
            alert('{{ __('messages.staff.select_at_least_one') }}');
            return;
        }

        bulkStoreId.value = modalStoreId.value;

        if (confirm('{{ __('messages.staff.confirm_bulk_payment_message') }}')) {
            form.submit();
        }
    });

    // Initial calculation
    updateTotals();
});
</script>
@endsection
