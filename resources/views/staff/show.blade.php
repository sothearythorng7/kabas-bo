@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="crud_title mb-0">
                {{ $staffMember->name }}
                @if($staffMember->contract_status === 'terminated')
                    <span class="badge bg-danger ms-2">{{ __('messages.staff.contract_status.terminated') }}</span>
                @else
                    <span class="badge bg-success ms-2">{{ __('messages.staff.contract_status.active') }}</span>
                @endif
            </h1>
            @if($staffMember->contract_status === 'terminated' && $staffMember->contract_end_date)
                <small class="text-muted">
                    {{ __('messages.staff.terminated_on') }}: {{ $staffMember->contract_end_date->format('d/m/Y') }}
                    @if($staffMember->termination_reason)
                        - {{ $staffMember->termination_reason }}
                    @endif
                </small>
            @endif
        </div>
        <div>
            @if($staffMember->contract_status === 'active')
                <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#terminateModal">
                    <i class="bi bi-person-x"></i> {{ __('messages.staff.terminate_contract') }}
                </button>
            @else
                <form action="{{ route('staff.reactivate', $staffMember) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success me-2" onclick="return confirm('{{ __('messages.staff.confirm_reactivate') }}')">
                        <i class="bi bi-person-check"></i> {{ __('messages.staff.reactivate_contract') }}
                    </button>
                </form>
            @endif
            <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="staffTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'info' ? 'active' : '' }}" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                <i class="bi bi-person"></i> {{ __('messages.staff.tab_info') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'documents' ? 'active' : '' }}" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">
                <i class="bi bi-folder"></i> {{ __('messages.staff.tab_documents') }}
                <span class="badge bg-{{ $staffMember->documents->count() > 0 ? 'primary' : 'secondary' }}">{{ $staffMember->documents->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'salary' ? 'active' : '' }}" id="salary-tab" data-bs-toggle="tab" data-bs-target="#salary" type="button" role="tab">
                <i class="bi bi-cash-stack"></i> {{ __('messages.staff.tab_salary') }}
                @if($staffMember->salaryAdvances->where('status', 'pending')->count() > 0)
                    <span class="badge bg-warning text-dark">{{ $staffMember->salaryAdvances->where('status', 'pending')->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'leaves' ? 'active' : '' }}" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves" type="button" role="tab">
                <i class="bi bi-calendar-x"></i> {{ __('messages.staff.tab_leaves') }}
                @if($staffMember->leaves->where('status', 'pending')->count() > 0)
                    <span class="badge bg-warning text-dark">{{ $staffMember->leaves->where('status', 'pending')->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'schedule' ? 'active' : '' }}" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab">
                <i class="bi bi-clock"></i> {{ __('messages.staff.tab_schedule') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'payroll' ? 'active' : '' }}" id="payroll-tab" data-bs-toggle="tab" data-bs-target="#payroll" type="button" role="tab">
                <i class="bi bi-wallet2"></i> {{ __('messages.staff.tab_payroll') }}
                <span class="badge bg-{{ $staffMember->salaryPayments->count() > 0 ? 'success' : 'secondary' }}">{{ $staffMember->salaryPayments->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'quotas' ? 'active' : '' }}" id="quotas-tab" data-bs-toggle="tab" data-bs-target="#quotas" type="button" role="tab">
                <i class="bi bi-calendar-check"></i> {{ __('messages.staff.tab_quotas') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'commissions' ? 'active' : '' }}" id="commissions-tab" data-bs-toggle="tab" data-bs-target="#commissions" type="button" role="tab">
                <i class="bi bi-percent"></i> {{ __('messages.staff.tab_commissions') }}
                @if($staffMember->employeeCommissions->where('is_active', true)->count() > 0)
                    <span class="badge bg-success">{{ $staffMember->employeeCommissions->where('is_active', true)->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'adjustments' ? 'active' : '' }}" id="adjustments-tab" data-bs-toggle="tab" data-bs-target="#adjustments" type="button" role="tab">
                <i class="bi bi-sliders"></i> {{ __('messages.staff.tab_adjustments') }}
                @if($staffMember->salaryAdjustments->where('status', 'pending')->count() > 0)
                    <span class="badge bg-warning text-dark">{{ $staffMember->salaryAdjustments->where('status', 'pending')->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'planning' ? 'active' : '' }}" id="planning-tab" data-bs-toggle="tab" data-bs-target="#planning" type="button" role="tab">
                <i class="bi bi-calendar-week"></i> {{ __('messages.staff.tab_planning') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'performance' ? 'active' : '' }}" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">
                <i class="bi bi-graph-up"></i> {{ __('messages.staff.tab_performance') }}
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="staffTabsContent">
        {{-- Onglet Info --}}
        <div class="tab-pane fade {{ $tab === 'info' ? 'show active' : '' }}" id="info" role="tabpanel">
            @include('staff.partials.tab-info')
        </div>

        {{-- Onglet Documents --}}
        <div class="tab-pane fade {{ $tab === 'documents' ? 'show active' : '' }}" id="documents" role="tabpanel">
            @include('staff.partials.tab-documents')
        </div>

        {{-- Onglet Salaire --}}
        <div class="tab-pane fade {{ $tab === 'salary' ? 'show active' : '' }}" id="salary" role="tabpanel">
            @include('staff.partials.tab-salary')
        </div>

        {{-- Onglet Congés --}}
        <div class="tab-pane fade {{ $tab === 'leaves' ? 'show active' : '' }}" id="leaves" role="tabpanel">
            @include('staff.partials.tab-leaves')
        </div>

        {{-- Onglet Horaires --}}
        <div class="tab-pane fade {{ $tab === 'schedule' ? 'show active' : '' }}" id="schedule" role="tabpanel">
            @include('staff.partials.tab-schedule')
        </div>

        {{-- Onglet Historique paie --}}
        <div class="tab-pane fade {{ $tab === 'payroll' ? 'show active' : '' }}" id="payroll" role="tabpanel">
            @include('staff.partials.tab-payroll')
        </div>

        {{-- Onglet Quotas --}}
        <div class="tab-pane fade {{ $tab === 'quotas' ? 'show active' : '' }}" id="quotas" role="tabpanel">
            @include('staff.partials.tab-quotas')
        </div>

        {{-- Onglet Commissions --}}
        <div class="tab-pane fade {{ $tab === 'commissions' ? 'show active' : '' }}" id="commissions" role="tabpanel">
            @include('staff.partials.tab-commissions')
        </div>

        {{-- Onglet Ajustements --}}
        <div class="tab-pane fade {{ $tab === 'adjustments' ? 'show active' : '' }}" id="adjustments" role="tabpanel">
            @include('staff.partials.tab-adjustments')
        </div>

        {{-- Onglet Planning --}}
        <div class="tab-pane fade {{ $tab === 'planning' ? 'show active' : '' }}" id="planning" role="tabpanel">
            @include('staff.partials.tab-user-planning')
        </div>

        {{-- Onglet Performance --}}
        <div class="tab-pane fade {{ $tab === 'performance' ? 'show active' : '' }}" id="performance" role="tabpanel">
            @include('staff.partials.tab-performance')
        </div>
    </div>
</div>

{{-- Modal Termination --}}
@if($staffMember->contract_status === 'active')
<div class="modal fade" id="terminateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.terminate', $staffMember) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-x"></i> {{ __('messages.staff.terminate_contract') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        {{ __('messages.staff.terminate_warning') }}
                    </div>

                    <div class="mb-3">
                        <label for="contract_end_date" class="form-label">{{ __('messages.staff.contract_end_date') }} *</label>
                        <input type="date" class="form-control" id="contract_end_date" name="contract_end_date"
                               value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="termination_reason" class="form-label">{{ __('messages.staff.termination_reason') }}</label>
                        <textarea class="form-control" id="termination_reason" name="termination_reason"
                                  rows="3" placeholder="{{ __('messages.staff.termination_reason_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-person-x"></i> {{ __('messages.staff.confirm_terminate') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');

    if (tab) {
        const tabBtn = document.getElementById(tab + '-tab');
        if (tabBtn) {
            tabBtn.click();
        }
    }
});
</script>
@endsection
