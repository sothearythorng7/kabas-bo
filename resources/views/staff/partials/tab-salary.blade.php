@php
    $stores = \App\Models\Store::orderBy('name')->get();
@endphp

<div class="row">
    {{-- Net Payroll Calculator --}}
    <div class="col-12 mb-3">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calculator"></i> {{ __('messages.staff.payroll_summary') }}</h5>
                <div>
                    <input type="month" id="payroll_month" class="form-control form-control-sm d-inline-block" style="width: auto;"
                           value="{{ $payrollData['month'] }}"
                           onchange="window.location.href='{{ route('staff.show', $staffMember) }}?tab=salary&payroll_month=' + this.value">
                </div>
            </div>
            <div class="card-body">
                @if($staffMember->currentSalary)
                    @php
                        $alreadyPaid = $staffMember->salaryPayments->contains('period', $payrollData['month']);
                    @endphp

                    <div class="row align-items-start">
                        <div class="col-md-8">
                            <table class="table table-sm table-borderless mb-0">
                                {{-- === GAINS === --}}
                                <tr>
                                    <td colspan="2" class="text-uppercase small fw-bold text-muted pb-0"><i class="bi bi-plus-circle"></i> {{ __('messages.staff.earnings') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('messages.staff.base_salary') }}</td>
                                    <td class="text-end fw-bold" id="line_base_salary">+ {{ number_format($payrollData['base_salary'], 2) }} {{ $payrollData['currency'] }}</td>
                                </tr>
                                <tr class="{{ $payrollData['overtime_amount'] > 0 ? '' : 'text-muted' }}">
                                    <td>{{ __('messages.staff.overtime') }}</td>
                                    <td class="text-end {{ $payrollData['overtime_amount'] > 0 ? 'text-success fw-bold' : '' }}">+ {{ number_format($payrollData['overtime_amount'], 2) }} {{ $payrollData['currency'] }}</td>
                                </tr>
                                <tr class="{{ $payrollData['bonus_amount'] > 0 ? '' : 'text-muted' }}">
                                    <td>{{ __('messages.staff.bonus') }}</td>
                                    <td class="text-end {{ $payrollData['bonus_amount'] > 0 ? 'text-success fw-bold' : '' }}">+ {{ number_format($payrollData['bonus_amount'], 2) }} {{ $payrollData['currency'] }}</td>
                                </tr>
                                <tr class="{{ $payrollData['commission_amount'] > 0 ? '' : 'text-muted' }}">
                                    <td>
                                        {{ __('messages.staff.commission') }}
                                        @if(!empty($commissionSummary['details']) && $commissionSummary['details']->count() > 0)
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-1" data-bs-toggle="collapse" data-bs-target="#commissionDetails">
                                                <i class="bi bi-info-circle"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-end {{ $payrollData['commission_amount'] > 0 ? 'text-success fw-bold' : '' }}">+ {{ number_format($payrollData['commission_amount'], 2) }} {{ $payrollData['currency'] }}</td>
                                </tr>
                                <tr class="{{ ($payrollData['other_adjustment_amount'] ?? 0) > 0 ? '' : 'text-muted' }}">
                                    <td>{{ __('messages.staff.other_adjustment') }}</td>
                                    <td class="text-end {{ ($payrollData['other_adjustment_amount'] ?? 0) > 0 ? 'text-success fw-bold' : '' }}">+ {{ number_format($payrollData['other_adjustment_amount'] ?? 0, 2) }} {{ $payrollData['currency'] }}</td>
                                </tr>
                                @if(!empty($commissionSummary['details']) && $commissionSummary['details']->count() > 0)
                                <tr class="collapse" id="commissionDetails">
                                    <td colspan="2">
                                        <div class="ps-3 small text-muted">
                                            @foreach($commissionSummary['details'] as $detail)
                                                <div class="d-flex justify-content-between">
                                                    <span>{{ $detail->employeeCommission?->getSourceName() }} - {{ __('messages.staff.turnover') }}: {{ number_format($detail->base_amount, 2) }} &times; {{ number_format($detail->employeeCommission?->percentage, 2) }}%</span>
                                                    <span>= {{ number_format($detail->commission_amount, 2) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                @endif

                                {{-- === SALAIRE BRUT === --}}
                                <tr class="border-top">
                                    <td class="fw-bold">{{ __('messages.staff.gross_salary') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($payrollData['gross_salary'], 2) }} {{ $payrollData['currency'] }}</td>
                                </tr>

                                {{-- === DEDUCTIONS === --}}
                                <tr>
                                    <td colspan="2" class="text-uppercase small fw-bold text-muted pt-2 pb-0"><i class="bi bi-dash-circle"></i> {{ __('messages.staff.deductions') }}</td>
                                </tr>
                                <tr class="{{ $payrollData['unjustified_days'] > 0 ? '' : 'text-muted' }}">
                                    <td>
                                        {{ __('messages.staff.unjustified_absences') }}
                                        @if($payrollData['unjustified_days'] > 0)
                                            <span class="badge bg-danger">{{ $payrollData['unjustified_days'] }} {{ __('messages.staff.days_abbr') }}</span>
                                        @endif
                                        <span class="text-muted">&times;</span>
                                        <input type="number" id="daily_rate" class="form-control form-control-sm d-inline-block" style="width: 100px;"
                                               value="{{ $payrollData['suggested_daily_rate'] }}" step="0.00001" min="0">
                                    </td>
                                    <td class="text-end {{ $payrollData['unjustified_days'] > 0 ? 'text-danger fw-bold' : '' }}" id="deduction_absences">
                                        - {{ number_format($payrollData['unjustified_days'] * $payrollData['suggested_daily_rate'], 2) }} {{ $payrollData['currency'] }}
                                    </td>
                                </tr>
                                <tr class="{{ $payrollData['advances_total'] > 0 ? '' : 'text-muted' }}">
                                    <td>{{ __('messages.staff.advances_deduction') }}</td>
                                    <td class="text-end {{ $payrollData['advances_total'] > 0 ? 'text-danger fw-bold' : '' }}" id="deduction_advances">
                                        - {{ number_format($payrollData['advances_total'], 2) }} {{ $payrollData['currency'] }}
                                    </td>
                                </tr>
                                <tr class="{{ $payrollData['penalty_amount'] > 0 ? '' : 'text-muted' }}">
                                    <td>{{ __('messages.staff.penalty') }}</td>
                                    <td class="text-end {{ $payrollData['penalty_amount'] > 0 ? 'text-danger fw-bold' : '' }}">- {{ number_format($payrollData['penalty_amount'], 2) }} {{ $payrollData['currency'] }}</td>
                                </tr>

                                {{-- === NET === --}}
                                <tr class="border-top border-2">
                                    <td class="fw-bold fs-5">{{ __('messages.staff.net_to_pay') }}</td>
                                    <td class="text-end fw-bold fs-4 text-success" id="net_salary">
                                        {{ number_format($payrollData['net_amount'], 2) }} {{ $payrollData['currency'] }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4 text-center border-start">
                            @if($alreadyPaid)
                                <div class="text-success mb-2">
                                    <i class="bi bi-check-circle-fill fs-1"></i>
                                </div>
                                <div class="fw-bold text-success">{{ __('messages.staff.already_paid') }}</div>
                            @else
                                <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#validatePaymentModal">
                                    <i class="bi bi-check-circle"></i> {{ __('messages.staff.validate_payment') }}
                                </button>
                                <div class="text-muted small mt-2">{{ __('messages.staff.suggested_daily_rate') }}: {{ number_format($payrollData['suggested_daily_rate'], 2) }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Modal de validation --}}
                    @if(!$alreadyPaid)
                    <div class="modal fade" id="validatePaymentModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <form action="{{ route('staff.payments.store', $staffMember) }}" method="POST" id="paymentForm">
                                @csrf
                                <input type="hidden" name="period" value="{{ $payrollData['month'] }}">
                                <input type="hidden" name="base_salary" value="{{ $payrollData['base_salary'] }}">
                                <input type="hidden" name="currency" value="{{ $payrollData['currency'] }}">
                                <input type="hidden" name="unjustified_days" value="{{ $payrollData['unjustified_days'] }}">
                                <input type="hidden" name="daily_rate" id="form_daily_rate" value="{{ $payrollData['suggested_daily_rate'] }}">
                                <input type="hidden" name="advances_deduction" value="{{ $payrollData['advances_total'] }}">
                                <input type="hidden" name="overtime_amount" value="{{ $payrollData['overtime_amount'] }}">
                                <input type="hidden" name="bonus_amount" value="{{ $payrollData['bonus_amount'] }}">
                                <input type="hidden" name="penalty_amount" value="{{ $payrollData['penalty_amount'] }}">
                                <input type="hidden" name="commission_amount" value="{{ $payrollData['commission_amount'] }}">
                                <input type="hidden" name="other_adjustment_amount" value="{{ $payrollData['other_adjustment_amount'] ?? 0 }}">
                                <input type="hidden" name="net_amount" id="form_net_amount" value="{{ $payrollData['net_amount'] }}">

                                <div class="modal-content">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title">{{ __('messages.staff.validate_payment') }} - {{ \Carbon\Carbon::parse($payrollData['month'] . '-01')->translatedFormat('F Y') }}</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        {{-- Recap complet --}}
                                        <table class="table table-sm table-borderless mb-3">
                                            {{-- Gains --}}
                                            <tr>
                                                <td colspan="2" class="text-uppercase small fw-bold text-muted pb-0"><i class="bi bi-plus-circle"></i> {{ __('messages.staff.earnings') }}</td>
                                            </tr>
                                            <tr>
                                                <td>{{ __('messages.staff.base_salary') }}</td>
                                                <td class="text-end">+ {{ number_format($payrollData['base_salary'], 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                            <tr class="{{ $payrollData['overtime_amount'] > 0 ? '' : 'text-muted' }}">
                                                <td>{{ __('messages.staff.overtime') }}</td>
                                                <td class="text-end {{ $payrollData['overtime_amount'] > 0 ? 'text-success' : '' }}">+ {{ number_format($payrollData['overtime_amount'], 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                            <tr class="{{ $payrollData['bonus_amount'] > 0 ? '' : 'text-muted' }}">
                                                <td>{{ __('messages.staff.bonus') }}</td>
                                                <td class="text-end {{ $payrollData['bonus_amount'] > 0 ? 'text-success' : '' }}">+ {{ number_format($payrollData['bonus_amount'], 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                            <tr class="{{ $payrollData['commission_amount'] > 0 ? '' : 'text-muted' }}">
                                                <td>{{ __('messages.staff.commission') }}</td>
                                                <td class="text-end {{ $payrollData['commission_amount'] > 0 ? 'text-success' : '' }}">+ {{ number_format($payrollData['commission_amount'], 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                            <tr class="{{ ($payrollData['other_adjustment_amount'] ?? 0) > 0 ? '' : 'text-muted' }}">
                                                <td>{{ __('messages.staff.other_adjustment') }}</td>
                                                <td class="text-end {{ ($payrollData['other_adjustment_amount'] ?? 0) > 0 ? 'text-success' : '' }}">+ {{ number_format($payrollData['other_adjustment_amount'] ?? 0, 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                            <tr class="border-top fw-bold">
                                                <td>{{ __('messages.staff.gross_salary') }}</td>
                                                <td class="text-end">{{ number_format($payrollData['gross_salary'], 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                            {{-- Déductions --}}
                                            <tr>
                                                <td colspan="2" class="text-uppercase small fw-bold text-muted pt-2 pb-0"><i class="bi bi-dash-circle"></i> {{ __('messages.staff.deductions') }}</td>
                                            </tr>
                                            <tr class="{{ $payrollData['unjustified_days'] > 0 ? 'text-danger' : 'text-muted' }}">
                                                <td>{{ __('messages.staff.unjustified_absences') }} ({{ $payrollData['unjustified_days'] }} {{ __('messages.staff.days_abbr') }})</td>
                                                <td class="text-end" id="modal_deduction_absences">- {{ number_format($payrollData['unjustified_days'] * $payrollData['suggested_daily_rate'], 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                            <tr class="{{ $payrollData['advances_total'] > 0 ? 'text-danger' : 'text-muted' }}">
                                                <td>{{ __('messages.staff.advances_deduction') }}</td>
                                                <td class="text-end">- {{ number_format($payrollData['advances_total'], 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                            <tr class="{{ $payrollData['penalty_amount'] > 0 ? 'text-danger' : 'text-muted' }}">
                                                <td>{{ __('messages.staff.penalty') }}</td>
                                                <td class="text-end">- {{ number_format($payrollData['penalty_amount'], 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                            {{-- Net --}}
                                            <tr class="border-top border-2 fw-bold fs-5">
                                                <td>{{ __('messages.staff.net_to_pay') }}</td>
                                                <td class="text-end text-success" id="modal_net_amount">{{ number_format($payrollData['net_amount'], 2) }} {{ $payrollData['currency'] }}</td>
                                            </tr>
                                        </table>

                                        <div class="mb-3">
                                            <label for="payment_store" class="form-label">{{ __('messages.staff.payment_from_store') }} *</label>
                                            <select class="form-select" id="payment_store" name="store_id" required>
                                                @foreach($stores as $store)
                                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="payment_notes" class="form-label">{{ __('messages.staff.notes') }}</label>
                                            <textarea class="form-control" id="payment_notes" name="notes" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check-circle"></i> {{ __('messages.staff.confirm_payment') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif
                @else
                    <p class="text-muted text-center mb-0">{{ __('messages.staff.no_salary_defined') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Current Salary & Form --}}
    <div class="col-md-4">
        {{-- Current Salary --}}
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">{{ __('messages.staff.current_salary') }}</h5>
            </div>
            <div class="card-body text-center">
                @if($staffMember->currentSalary)
                    <h2 class="mb-0">{{ number_format($staffMember->currentSalary->base_salary, 2) }} {{ $staffMember->currentSalary->currency }}</h2>
                    <small class="text-muted">{{ __('messages.staff.effective_from') }}: {{ $staffMember->currentSalary->effective_from->format('d/m/Y') }}</small>
                @else
                    <p class="text-muted mb-0">{{ __('messages.staff.no_salary_defined') }}</p>
                @endif
            </div>
        </div>

        {{-- Set Salary Form --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.staff.set_salary') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.salary.update', $staffMember) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="base_salary" class="form-label">{{ __('messages.staff.base_salary') }} *</label>
                        <input type="number" step="0.00001" class="form-control" id="base_salary" name="base_salary"
                               value="{{ old('base_salary', $staffMember->currentSalary?->base_salary) }}" required min="0">
                    </div>
                    <div class="mb-3">
                        <label for="currency" class="form-label">{{ __('messages.staff.currency') }} *</label>
                        <select class="form-select" id="currency" name="currency" required>
                            <option value="USD" {{ old('currency', $staffMember->currentSalary?->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('currency', $staffMember->currentSalary?->currency) === 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="XAF" {{ old('currency', $staffMember->currentSalary?->currency) === 'XAF' ? 'selected' : '' }}>XAF</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="effective_from" class="form-label">{{ __('messages.staff.effective_from') }} *</label>
                        <input type="date" class="form-control" id="effective_from" name="effective_from"
                               value="{{ old('effective_from', now()->format('Y-m-d')) }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> {{ __('messages.btn.save') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Request Advance Form --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.staff.request_advance') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.advances.store', $staffMember) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="amount" class="form-label">{{ __('messages.staff.amount') }} *</label>
                        <input type="number" step="0.00001" class="form-control" id="amount" name="amount" required min="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">{{ __('messages.staff.reason') }}</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-plus-circle"></i> {{ __('messages.staff.create_advance') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Salary History & Advances --}}
    <div class="col-md-8">
        {{-- Pending Advances Total --}}
        @if($pendingAdvancesTotal > 0)
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                {{ __('messages.staff.total_approved_advances') }}: <strong>{{ number_format($pendingAdvancesTotal, 2) }} USD</strong>
            </div>
        @endif

        {{-- Salary History --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.staff.salary_history') }}</h5>
            </div>
            <div class="card-body">
                @if($staffMember->salaries->isEmpty())
                    <p class="text-muted text-center">{{ __('messages.staff.no_salary_history') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.staff.effective_from') }}</th>
                                    <th class="text-end">{{ __('messages.staff.amount') }}</th>
                                    <th>{{ __('messages.staff.created_by') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffMember->salaries->take(5) as $salary)
                                    <tr>
                                        <td>{{ $salary->effective_from->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ number_format($salary->base_salary, 2) }} {{ $salary->currency }}</td>
                                        <td>{{ $salary->creator->name ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Advances List --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.staff.advances') }}</h5>
            </div>
            <div class="card-body">
                @if($staffMember->salaryAdvances->isEmpty())
                    <p class="text-muted text-center">{{ __('messages.staff.no_advances') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.staff.date') }}</th>
                                    <th class="text-end">{{ __('messages.staff.amount') }}</th>
                                    <th>{{ __('messages.staff.reason') }}</th>
                                    <th>{{ __('messages.staff.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffMember->salaryAdvances as $advance)
                                    <tr>
                                        <td>{{ $advance->requested_at->format('d/m/Y H:i') }}</td>
                                        <td class="text-end"><strong>{{ number_format($advance->amount, 2) }}</strong></td>
                                        <td>{{ Str::limit($advance->reason, 30) ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $advance->getStatusBadgeClass() }}">
                                                {{ $advance->getStatusLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($advance->status === 'pending')
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveAdvanceModal{{ $advance->id }}">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <form action="{{ route('staff.advances.approve', $advance) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('messages.staff.confirm_reject') }}')">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </form>

                                                {{-- Approve Modal --}}
                                                <div class="modal fade" id="approveAdvanceModal{{ $advance->id }}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <form action="{{ route('staff.advances.approve', $advance) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="action" value="approve">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">{{ __('messages.staff.approve_advance') }}</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>{{ __('messages.staff.approve_advance_amount') }}: <strong>{{ number_format($advance->amount, 2) }}</strong></p>
                                                                    <div class="mb-3">
                                                                        <label for="store_id" class="form-label">{{ __('messages.staff.select_store') }} *</label>
                                                                        <select class="form-select" name="store_id" required>
                                                                            @foreach($stores as $store)
                                                                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        <small class="text-muted">{{ __('messages.staff.store_for_transaction') }}</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                                                    <button type="submit" class="btn btn-success">{{ __('messages.btn.approve') }}</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @else
                                                @if($advance->approver)
                                                    <small class="text-muted">{{ $advance->approver->name }}</small>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($staffMember->currentSalary)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dailyRateInput = document.getElementById('daily_rate');
    const baseSalary = {{ $payrollData['base_salary'] }};
    const unjustifiedDays = {{ $payrollData['unjustified_days'] }};
    const advancesTotal = {{ $payrollData['advances_total'] }};
    const overtimeAmount = {{ $payrollData['overtime_amount'] }};
    const bonusAmount = {{ $payrollData['bonus_amount'] }};
    const penaltyAmount = {{ $payrollData['penalty_amount'] }};
    const commissionAmount = {{ $payrollData['commission_amount'] }};
    const otherAdjustmentAmount = {{ $payrollData['other_adjustment_amount'] ?? 0 }};
    const currency = '{{ $payrollData['currency'] }}';

    function formatNumber(num) {
        return num.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalculate() {
        const dailyRate = parseFloat(dailyRateInput.value) || 0;
        const deductionAbsences = unjustifiedDays * dailyRate;
        const grossSalary = baseSalary + overtimeAmount + bonusAmount + commissionAmount + otherAdjustmentAmount;
        const totalDeductions = deductionAbsences + advancesTotal + penaltyAmount;
        const netSalary = grossSalary - totalDeductions;

        document.getElementById('deduction_absences').textContent = '- ' + formatNumber(deductionAbsences) + ' ' + currency;
        document.getElementById('net_salary').textContent = formatNumber(netSalary) + ' ' + currency;

        // Update hidden form fields for payment
        const formDailyRate = document.getElementById('form_daily_rate');
        const formNetAmount = document.getElementById('form_net_amount');
        const modalNetAmount = document.getElementById('modal_net_amount');
        const modalDeductionAbsences = document.getElementById('modal_deduction_absences');

        if (formDailyRate) formDailyRate.value = dailyRate.toFixed(5);
        if (formNetAmount) formNetAmount.value = netSalary.toFixed(5);
        if (modalNetAmount) modalNetAmount.textContent = formatNumber(netSalary) + ' ' + currency;
        if (modalDeductionAbsences) modalDeductionAbsences.textContent = '- ' + formatNumber(deductionAbsences) + ' ' + currency;

        // Change color based on positive/negative
        const netElement = document.getElementById('net_salary');
        if (netSalary < 0) {
            netElement.classList.remove('text-success');
            netElement.classList.add('text-danger');
        } else {
            netElement.classList.remove('text-danger');
            netElement.classList.add('text-success');
        }
    }

    dailyRateInput.addEventListener('input', recalculate);
});
</script>
@endif
