<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> {{ __('messages.staff.payment_history') }}</h5>
    </div>
    <div class="card-body">
        @if($user->salaryPayments->isEmpty())
            <p class="text-muted text-center">{{ __('messages.staff.no_payments') }}</p>
        @else
            {{-- Totals Summary --}}
            <div class="row text-center mb-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">{{ __('messages.staff.total_paid_year') }}</div>
                            <div class="fs-4 fw-bold text-success">
                                {{ number_format($user->salaryPayments->filter(fn($p) => $p->period >= now()->startOfYear()->format('Y-m'))->sum('net_amount'), 2) }}
                                {{ $user->currentSalary?->currency ?? 'USD' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">{{ __('messages.staff.payments_count') }}</div>
                            <div class="fs-4 fw-bold">{{ $user->salaryPayments->count() }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">{{ __('messages.staff.last_payment') }}</div>
                            <div class="fs-5 fw-bold">{{ $user->salaryPayments->first()?->period_label ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('messages.staff.period') }}</th>
                            <th class="text-end">{{ __('messages.staff.base_salary') }}</th>
                            <th class="text-end">{{ __('messages.staff.deductions') }}</th>
                            <th class="text-end">{{ __('messages.staff.net_paid') }}</th>
                            <th>{{ __('messages.staff.paid_by') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->salaryPayments as $payment)
                            <tr>
                                <td>
                                    <strong>{{ $payment->period_label }}</strong>
                                    <br><small class="text-muted">{{ $payment->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td class="text-end">{{ number_format($payment->base_salary, 2) }}</td>
                                <td class="text-end text-danger">
                                    @if($payment->total_deductions > 0)
                                        - {{ number_format($payment->total_deductions, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong class="text-success">{{ number_format($payment->net_amount, 2) }} {{ $payment->currency }}</strong>
                                </td>
                                <td>{{ $payment->payer->name ?? '-' }}</td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#paymentDetailModal{{ $payment->id }}" title="{{ __('messages.staff.view_details') }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="{{ route('staff.payments.payslip', $payment) }}" class="btn btn-sm btn-secondary" title="{{ __('messages.staff.download_payslip') }}">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>

                            {{-- Payment Detail Modal --}}
                            <div class="modal fade" id="paymentDetailModal{{ $payment->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">{{ __('messages.staff.payment_details') }} - {{ $payment->period_label }}</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td class="text-muted">{{ __('messages.staff.base_salary') }}</td>
                                                    <td class="text-end">{{ number_format($payment->base_salary, 2) }} {{ $payment->currency }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">{{ __('messages.staff.daily_rate') }}</td>
                                                    <td class="text-end">{{ number_format($payment->daily_rate, 2) }} {{ $payment->currency }}</td>
                                                </tr>
                                                <tr class="text-danger">
                                                    <td>{{ __('messages.staff.unjustified_absences') }} ({{ $payment->unjustified_days }} {{ __('messages.staff.days_abbr') }})</td>
                                                    <td class="text-end">- {{ number_format($payment->absence_deduction, 2) }} {{ $payment->currency }}</td>
                                                </tr>
                                                <tr class="text-danger">
                                                    <td>{{ __('messages.staff.advances_deduction') }}</td>
                                                    <td class="text-end">- {{ number_format($payment->advances_deduction, 2) }} {{ $payment->currency }}</td>
                                                </tr>
                                                <tr class="border-top fw-bold fs-5">
                                                    <td>{{ __('messages.staff.net_paid') }}</td>
                                                    <td class="text-end text-success">{{ number_format($payment->net_amount, 2) }} {{ $payment->currency }}</td>
                                                </tr>
                                            </table>

                                            <hr>

                                            <div class="small text-muted">
                                                <p class="mb-1"><strong>{{ __('messages.staff.paid_by') }}:</strong> {{ $payment->payer->name ?? '-' }}</p>
                                                <p class="mb-1"><strong>{{ __('messages.staff.payment_from_store') }}:</strong> {{ $payment->store->name ?? '-' }}</p>
                                                <p class="mb-1"><strong>{{ __('messages.staff.date') }}:</strong> {{ $payment->created_at->format('d/m/Y H:i') }}</p>
                                                @if($payment->notes)
                                                    <p class="mb-1"><strong>{{ __('messages.staff.notes') }}:</strong> {{ $payment->notes }}</p>
                                                @endif
                                                @if($payment->financial_transaction_id)
                                                    <p class="mb-0"><strong>{{ __('messages.staff.transaction_id') }}:</strong> #{{ $payment->financial_transaction_id }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.close') }}</button>
                                            <a href="{{ route('staff.payments.payslip', $payment) }}" class="btn btn-primary">
                                                <i class="bi bi-file-pdf"></i> {{ __('messages.staff.download_payslip') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
