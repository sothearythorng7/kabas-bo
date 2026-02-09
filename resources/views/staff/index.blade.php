@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title mb-0">{{ __('messages.staff.title') }}</h1>
        <div class="d-flex gap-2">
            @if($tab === 'list' && $pendingPaymentsCount > 0)
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkPaymentModal">
                    <i class="bi bi-cash-stack"></i> {{ __('messages.staff.pay_all') }}
                    <span class="badge bg-light text-success">{{ $pendingPaymentsCount }}</span>
                </button>
            @endif
            @if($tab === 'list')
                <a href="{{ route('staff.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> {{ __('messages.staff.add_employee') }}
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Onglets --}}
    <ul class="nav nav-tabs mb-4" id="staffMainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'list' ? 'active' : '' }}" href="{{ route('staff.index', ['tab' => 'list']) }}">
                <i class="bi bi-people"></i> {{ __('messages.staff.tab_list') }}
                @if($pendingPaymentsCount > 0)
                    <span class="badge bg-warning text-dark">{{ $pendingPaymentsCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'planning' ? 'active' : '' }}" href="{{ route('staff.index', ['tab' => 'planning', 'month' => request('month', now()->format('Y-m')), 'store_id' => request('store_id')]) }}">
                <i class="bi bi-calendar-week"></i> {{ __('messages.staff.tab_planning') }}
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'performance' ? 'active' : '' }}" href="{{ route('staff.index', ['tab' => 'performance', 'period' => request('period', now()->format('Y-m')), 'store_id' => request('store_id')]) }}">
                <i class="bi bi-graph-up"></i> {{ __('messages.staff.tab_performances') }}
            </a>
        </li>
    </ul>

    {{-- Contenu des onglets --}}
    @if($tab === 'list')
        @include('staff.partials.index-list')
    @elseif($tab === 'planning')
        @include('staff.partials.index-planning')
    @elseif($tab === 'performance')
        @include('staff.partials.index-performance')
    @endif
</div>

@if($tab === 'list')
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

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
            updateTotals();
        });
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateTotals);
    });

    if (confirmBtn) {
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
    }

    updateTotals();
});
</script>
@endif
@endsection
