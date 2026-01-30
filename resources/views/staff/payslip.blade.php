<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.staff.payslip') }} - {{ $payment->user->name }} - {{ $payment->period_label }}</title>
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
        table.payslip-table tr.deduction td {
            color: #c00;
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
                <div class="info-value">{{ $payment->user->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.email') }}</div>
                <div class="info-value">{{ $payment->user->email }}</div>
            </div>
            @if($payment->user->phone)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.phone') }}</div>
                <div class="info-value">{{ $payment->user->phone }}</div>
            </div>
            @endif
            @if($payment->user->address)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.address') }}</div>
                <div class="info-value">{{ $payment->user->address }}</div>
            </div>
            @endif
            @if($payment->user->store)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.store') }}</div>
                <div class="info-value">{{ $payment->user->store->name }}</div>
            </div>
            @endif
            @if($payment->user->hire_date)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.hire_date') }}</div>
                <div class="info-value">{{ $payment->user->hire_date->format('d/m/Y') }}</div>
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
                <tr>
                    <td>{{ __('messages.staff.base_salary') }}</td>
                    <td class="amount">{{ number_format($payment->base_salary, 2) }} {{ $payment->currency }}</td>
                </tr>

                @if($payment->unjustified_days > 0)
                <tr class="deduction">
                    <td>
                        {{ __('messages.staff.absence_deduction') }}
                        <br>
                        <small>({{ $payment->unjustified_days }} {{ __('messages.staff.days_abbr') }} × {{ number_format($payment->daily_rate, 2) }} {{ $payment->currency }})</small>
                    </td>
                    <td class="amount">- {{ number_format($payment->absence_deduction, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                @if($payment->advances_deduction > 0)
                <tr class="deduction">
                    <td>{{ __('messages.staff.advances_deduction') }}</td>
                    <td class="amount">- {{ number_format($payment->advances_deduction, 2) }} {{ $payment->currency }}</td>
                </tr>
                @endif

                <tr class="total">
                    <td>{{ __('messages.staff.net_paid') }}</td>
                    <td class="amount">{{ number_format($payment->net_amount, 2) }} {{ $payment->currency }}</td>
                </tr>
            </tbody>
        </table>
    </div>

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
            @if($payment->notes)
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff.notes') }}</div>
                <div class="info-value">{{ $payment->notes }}</div>
            </div>
            @endif
        </div>
    </div>

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
