<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.staff.payslip') }} - {{ $payment->staffMember->name }} - {{ $payment->period_label }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header .period {
            font-size: 16px;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            background: #f5f5f5;
            padding: 8px 10px;
            margin-bottom: 10px;
            border-left: 4px solid #333;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 5px 10px;
            color: #666;
            width: 40%;
        }
        .info-value {
            display: table-cell;
            padding: 5px 10px;
            font-weight: 500;
        }
        table.payslip-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.payslip-table th,
        table.payslip-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table.payslip-table th {
            background: #f5f5f5;
            font-weight: bold;
        }
        table.payslip-table td.amount {
            text-align: right;
            font-family: monospace;
            font-size: 13px;
        }
        table.payslip-table tr.addition td {
            color: #070;
        }
        table.payslip-table tr.deduction td {
            color: #c00;
        }
        table.payslip-table tr.subtotal {
            background: #f9f9f9;
        }
        table.payslip-table tr.subtotal td {
            font-weight: bold;
            border-top: 2px solid #999;
        }
        table.payslip-table tr.total {
            background: #333;
            color: #fff;
        }
        table.payslip-table tr.total td {
            font-weight: bold;
            font-size: 14px;
            border-color: #333;
        }
        table.commission-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.commission-table th,
        table.commission-table td {
            padding: 6px 10px;
            border: 1px solid #ddd;
            text-align: left;
            font-size: 11px;
        }
        table.commission-table th {
            background: #f5f5f5;
            font-weight: bold;
        }
        table.commission-table td.amount {
            text-align: right;
            font-family: monospace;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .signatures {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('messages.staff.payslip') }}</h1>
        <div class="period">{{ $payment->period_label }}</div>
    </div>

    <div class="section">
        <div class="section-title">{{ __('messages.staff.employee_info') }}</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.name') }}</div>
                <div class="info-value">{{ $payment->staffMember->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.email') }}</div>
                <div class="info-value">{{ $payment->staffMember->email }}</div>
            </div>
            @if($payment->staffMember->phone)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.phone') }}</div>
                <div class="info-value">{{ $payment->staffMember->phone }}</div>
            </div>
            @endif
            @if($payment->staffMember->address)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.address') }}</div>
                <div class="info-value">{{ $payment->staffMember->address }}</div>
            </div>
            @endif
            @if($payment->staffMember->store)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.store') }}</div>
                <div class="info-value">{{ $payment->staffMember->store->name }}</div>
            </div>
            @endif
            @if($payment->staffMember->hire_date)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.hire_date') }}</div>
                <div class="info-value">{{ $payment->staffMember->hire_date->format('d/m/Y') }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">{{ __('messages.staff.salary_details') }}</div>
        <table class="payslip-table">
            <thead>
                <tr>
                    <th>{{ __('messages.staff.description') }}</th>
                    <th style="width: 150px; text-align: right;">{{ __('messages.staff.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                {{-- Base Salary --}}
                <tr>
                    <td>{{ __('messages.staff.base_salary') }}</td>
                    <td class="amount">{{ number_format($payment->base_salary, 2) }} {{ $payment->currency }}</td>
                </tr>

                {{-- Overtime --}}
                @if($payment->overtime_amount > 0)
                <tr class="addition">
                    <td>{{ __('messages.staff.overtime') }}</td>
                    <td class="amount">+ {{ number_format($payment->overtime_amount, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                {{-- Bonus --}}
                @if($payment->bonus_amount > 0)
                <tr class="addition">
                    <td>{{ __('messages.staff.bonus') }}</td>
                    <td class="amount">+ {{ number_format($payment->bonus_amount, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                {{-- Commission --}}
                @if($payment->commission_amount > 0)
                <tr class="addition">
                    <td>{{ __('messages.staff.commission') }}</td>
                    <td class="amount">+ {{ number_format($payment->commission_amount, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                {{-- Other Adjustments --}}
                @if($payment->other_adjustment_amount > 0)
                <tr class="addition">
                    <td>{{ __('messages.staff.other_adjustment') }}</td>
                    <td class="amount">+ {{ number_format($payment->other_adjustment_amount, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                {{-- Gross Salary subtotal --}}
                @if($payment->total_additions > 0)
                <tr class="subtotal">
                    <td>{{ __('messages.staff.gross_salary') }}</td>
                    <td class="amount">{{ number_format($payment->gross_salary, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                {{-- Absence deduction --}}
                @if($payment->unjustified_days > 0)
                <tr class="deduction">
                    <td>
                        {{ __('messages.staff.absence_deduction') }}
                        <br>
                        <small>({{ $payment->unjustified_days }} {{ __('messages.staff.days_abbr') }} &times; {{ number_format($payment->daily_rate, 2) }} {{ $payment->currency }})</small>
                    </td>
                    <td class="amount">- {{ number_format($payment->absence_deduction, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                {{-- Advances deduction --}}
                @if($payment->advances_deduction > 0)
                <tr class="deduction">
                    <td>{{ __('messages.staff.advances_deduction') }}</td>
                    <td class="amount">- {{ number_format($payment->advances_deduction, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                {{-- Penalty --}}
                @if($payment->penalty_amount > 0)
                <tr class="deduction">
                    <td>{{ __('messages.staff.penalty') }}</td>
                    <td class="amount">- {{ number_format($payment->penalty_amount, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                {{-- Total Deductions subtotal --}}
                @if($payment->total_deductions > 0)
                <tr class="subtotal">
                    <td>{{ __('messages.staff.total_deductions') }}</td>
                    <td class="amount">- {{ number_format($payment->total_deductions, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                {{-- Net Paid --}}
                <tr class="total">
                    <td>{{ __('messages.staff.net_paid') }}</td>
                    <td class="amount">{{ number_format($payment->net_amount, 2) }} {{ $payment->currency }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Commission Details Section --}}
    @if($payment->commission_amount > 0 && !empty($commissionCalculations) && $commissionCalculations->count() > 0)
    <div class="section">
        <div class="section-title">{{ __('messages.staff.commission_details') }}</div>
        <table class="commission-table">
            <thead>
                <tr>
                    <th>{{ __('messages.staff.commission_source') }}</th>
                    <th style="text-align: right;">{{ __('messages.staff.turnover') }}</th>
                    <th style="text-align: right;">{{ __('messages.staff.percentage') }}</th>
                    <th style="text-align: right;">{{ __('messages.staff.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($commissionCalculations as $calc)
                <tr>
                    <td>{{ $calc->employeeCommission?->getSourceName() ?? '-' }}</td>
                    <td class="amount">{{ number_format($calc->base_amount, 2) }} {{ $payment->currency }}</td>
                    <td class="amount">{{ number_format($calc->employeeCommission?->percentage ?? 0, 2) }}%</td>
                    <td class="amount">{{ number_format($calc->commission_amount, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endforeach
                <tr style="font-weight: bold; background: #f5f5f5;">
                    <td colspan="3">{{ __('messages.staff.total') }}</td>
                    <td class="amount">{{ number_format($commissionCalculations->sum('commission_amount'), 2) }} {{ $payment->currency }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">{{ __('messages.staff.payment_info') }}</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.payment_date') }}</div>
                <div class="info-value">{{ $payment->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.paid_by') }}</div>
                <div class="info-value">{{ $payment->payer->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.payment_from_store') }}</div>
                <div class="info-value">{{ $payment->store->name ?? '-' }}</div>
            </div>
            @if($payment->is_transferred)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.transfer_status') }}</div>
                <div class="info-value">{{ __('messages.staff.transferred') }}{{ $payment->transferred_at ? ' - ' . $payment->transferred_at->format('d/m/Y') : '' }}</div>
            </div>
            @if($payment->transfer_reference)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.transfer_reference') }}</div>
                <div class="info-value">{{ $payment->transfer_reference }}</div>
            </div>
            @endif
            @endif
            @if($payment->notes)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.notes') }}</div>
                <div class="info-value">{{ $payment->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Leave Balances Section --}}
    @if(!empty($quotaBalances))
    <div class="section">
        <div class="section-title">{{ __('messages.staff.leave_balance_summary') }}</div>
        <table class="commission-table">
            <thead>
                <tr>
                    <th>{{ __('messages.staff.leave_type') }}</th>
                    <th style="text-align: right;">{{ __('messages.staff.quota_entitled') }}</th>
                    <th style="text-align: right;">{{ __('messages.staff.quota_used') }}</th>
                    <th style="text-align: right;">{{ __('messages.staff.quota_remaining') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotaBalances as $type => $balance)
                <tr>
                    <td>{{ __('messages.staff.leave_types.' . $type) }}</td>
                    <td class="amount">{{ $balance['accrued'] }}</td>
                    <td class="amount">{{ $balance['used'] }}</td>
                    <td class="amount" style="font-weight: bold;">{{ $balance['remaining'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">{{ __('messages.staff.employer_signature') }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">{{ __('messages.staff.employee_signature') }}</div>
        </div>
    </div>

    <div class="footer">
        {{ __('messages.staff.payslip_generated') }}: {{ now()->format('d/m/Y H:i') }}
        @if($payment->financial_transaction_id)
            | {{ __('messages.staff.transaction_id') }}: #{{ $payment->financial_transaction_id }}
        @endif
    </div>
</body>
</html>
