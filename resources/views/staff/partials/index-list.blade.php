{{-- Onglet Liste du personnel --}}

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
    <input type="hidden" name="tab" value="list">
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
        <a href="{{ route('staff.index', ['tab' => 'list']) }}" class="btn btn-secondary w-100">
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
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($staffMembers as $staffMember)
                @php
                    $payroll = $staffMember->payroll_calculated;
                    $canPay = $payroll['base_salary'] > 0 && !$payroll['is_paid'];
                @endphp
                <tr class="{{ $payroll['is_paid'] ? 'table-success' : '' }}">
                    <td>
                        @if($canPay)
                            <input type="checkbox" class="form-check-input user-checkbox"
                                   name="staff_member_ids[]" value="{{ $staffMember->id }}"
                                   data-amount="{{ $payroll['net_amount'] }}">
                        @endif
                    </td>
                    <td style="width: 1%; white-space: nowrap;">
                        <a href="{{ route('staff.show', $staffMember) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                    <td>
                        <strong>{{ $staffMember->name }}</strong>
                        @if($staffMember->user?->roles?->isNotEmpty())
                            <br><small class="text-muted">{{ $staffMember->user->roles->pluck('name')->join(', ') }}</small>
                        @endif
                    </td>
                    <td>{{ $staffMember->store->name ?? '-' }}</td>
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
                    <td class="text-nowrap">
                        @if($canPay)
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#generatePayslipModal{{ $staffMember->id }}" title="{{ __('messages.staff.generate_payslip') }}">
                                <i class="bi bi-file-earmark-text"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-muted text-center">{{ __('messages.staff.no_staff') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</form>

{{ $staffMembers->appends(request()->query())->links() }}

{{-- Generate Payslip Modals --}}
@foreach($staffMembers as $staffMember)
    @php
        $payroll = $staffMember->payroll_calculated;
        $canPay = $payroll['base_salary'] > 0 && !$payroll['is_paid'];
        $hasAdditions = ($payroll['overtime_amount'] ?? 0) > 0 || ($payroll['bonus_amount'] ?? 0) > 0 || ($payroll['commission_amount'] ?? 0) > 0;
    @endphp
    @if($canPay)
    <div class="modal fade" id="generatePayslipModal{{ $staffMember->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('staff.payments.store', $staffMember) }}" method="POST">
                @csrf
                <input type="hidden" name="period" value="{{ $currentMonth }}">
                <input type="hidden" name="base_salary" value="{{ $payroll['base_salary'] }}">
                <input type="hidden" name="currency" value="{{ $payroll['currency'] }}">
                <input type="hidden" name="unjustified_days" value="{{ $payroll['unjustified_days'] }}">
                <input type="hidden" name="daily_rate" value="{{ $payroll['daily_rate'] }}">
                <input type="hidden" name="advances_deduction" value="{{ $payroll['advances_total'] }}">
                <input type="hidden" name="overtime_amount" value="{{ $payroll['overtime_amount'] }}">
                <input type="hidden" name="bonus_amount" value="{{ $payroll['bonus_amount'] }}">
                <input type="hidden" name="penalty_amount" value="{{ $payroll['penalty_amount'] }}">
                <input type="hidden" name="commission_amount" value="{{ $payroll['commission_amount'] }}">
                <input type="hidden" name="net_amount" value="{{ $payroll['net_amount'] }}">

                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-file-earmark-text"></i>
                            {{ __('messages.staff.generate_payslip') }} - {{ $staffMember->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6 class="text-muted">{{ \Carbon\Carbon::parse($currentMonth . '-01')->translatedFormat('F Y') }}</h6>
                        </div>

                        {{-- Payroll Summary --}}
                        <table class="table table-sm table-borderless mb-3">
                            {{-- Gains --}}
                            <tr>
                                <td colspan="2" class="text-uppercase small fw-bold text-muted pb-0"><i class="bi bi-plus-circle"></i> {{ __('messages.staff.earnings') }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('messages.staff.base_salary') }}</td>
                                <td class="text-end">+ {{ number_format($payroll['base_salary'], 2) }} {{ $payroll['currency'] }}</td>
                            </tr>
                            <tr class="{{ ($payroll['overtime_amount'] ?? 0) > 0 ? '' : 'text-muted' }}">
                                <td>{{ __('messages.staff.overtime') }}</td>
                                <td class="text-end {{ ($payroll['overtime_amount'] ?? 0) > 0 ? 'text-success' : '' }}">+ {{ number_format($payroll['overtime_amount'] ?? 0, 2) }} {{ $payroll['currency'] }}</td>
                            </tr>
                            <tr class="{{ ($payroll['bonus_amount'] ?? 0) > 0 ? '' : 'text-muted' }}">
                                <td>{{ __('messages.staff.bonus') }}</td>
                                <td class="text-end {{ ($payroll['bonus_amount'] ?? 0) > 0 ? 'text-success' : '' }}">+ {{ number_format($payroll['bonus_amount'] ?? 0, 2) }} {{ $payroll['currency'] }}</td>
                            </tr>
                            <tr class="{{ ($payroll['commission_amount'] ?? 0) > 0 ? '' : 'text-muted' }}">
                                <td>
                                    {{ __('messages.staff.commission') }}
                                    @if(!empty($staffMember->commission_summary['details']) && $staffMember->commission_summary['details']->count() > 0)
                                        <button type="button" class="btn btn-sm btn-link p-0 ms-1" data-bs-toggle="collapse" data-bs-target="#indexCommDetails{{ $staffMember->id }}">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                    @endif
                                </td>
                                <td class="text-end {{ ($payroll['commission_amount'] ?? 0) > 0 ? 'text-success' : '' }}">+ {{ number_format($payroll['commission_amount'] ?? 0, 2) }} {{ $payroll['currency'] }}</td>
                            </tr>
                            @if(!empty($staffMember->commission_summary['details']) && $staffMember->commission_summary['details']->count() > 0)
                            <tr class="collapse" id="indexCommDetails{{ $staffMember->id }}">
                                <td colspan="2">
                                    <div class="ps-3 small text-muted">
                                        @foreach($staffMember->commission_summary['details'] as $detail)
                                            <div class="d-flex justify-content-between">
                                                <span>{{ $detail->employeeCommission?->getSourceName() }} - {{ __('messages.staff.turnover') }}: {{ number_format($detail->base_amount, 2) }} &times; {{ number_format($detail->employeeCommission?->percentage, 2) }}%</span>
                                                <span>= {{ number_format($detail->commission_amount, 2) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                            @endif

                            <tr class="border-top fw-bold">
                                <td>{{ __('messages.staff.gross_salary') }}</td>
                                <td class="text-end">{{ number_format($payroll['gross_salary'], 2) }} {{ $payroll['currency'] }}</td>
                            </tr>

                            {{-- Déductions --}}
                            <tr>
                                <td colspan="2" class="text-uppercase small fw-bold text-muted pt-2 pb-0"><i class="bi bi-dash-circle"></i> {{ __('messages.staff.deductions') }}</td>
                            </tr>
                            <tr class="{{ ($payroll['unjustified_days'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                                <td>{{ __('messages.staff.unjustified_absences') }} ({{ $payroll['unjustified_days'] ?? 0 }} {{ __('messages.staff.days_abbr') }} &times; {{ number_format($payroll['daily_rate'], 2) }})</td>
                                <td class="text-end">- {{ number_format($payroll['absence_deduction'] ?? 0, 2) }} {{ $payroll['currency'] }}</td>
                            </tr>
                            <tr class="{{ ($payroll['advances_total'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                                <td>{{ __('messages.staff.advances_deduction') }}</td>
                                <td class="text-end">- {{ number_format($payroll['advances_total'] ?? 0, 2) }} {{ $payroll['currency'] }}</td>
                            </tr>
                            <tr class="{{ ($payroll['penalty_amount'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                                <td>{{ __('messages.staff.penalty') }}</td>
                                <td class="text-end">- {{ number_format($payroll['penalty_amount'] ?? 0, 2) }} {{ $payroll['currency'] }}</td>
                            </tr>

                            {{-- Net --}}
                            <tr class="border-top border-2 fw-bold fs-5">
                                <td>{{ __('messages.staff.net_to_pay') }}</td>
                                <td class="text-end {{ $payroll['net_amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($payroll['net_amount'], 2) }} {{ $payroll['currency'] }}
                                </td>
                            </tr>
                        </table>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.staff.payment_from_store') }} *</label>
                            <select class="form-select" name="store_id" required>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ $staffMember->store_id == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.staff.notes') }}</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
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
@endforeach
