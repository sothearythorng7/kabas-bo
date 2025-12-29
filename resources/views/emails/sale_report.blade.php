<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.sale_reports.title') }} #{{ $saleReport->id }}</title>
</head>
<body>
    <p>{{ __('messages.emails.sale_report.greeting') }}</p>

    <p>{{ __('messages.emails.sale_report.intro') }} <strong>#{{ $saleReport->id }}</strong>
       {{ __('messages.emails.sale_report.period') }} <strong>{{ $saleReport->period_start->format('d/m/Y') }}</strong>
       - <strong>{{ $saleReport->period_end->format('d/m/Y') }}</strong>.</p>

    <p>{{ __('messages.emails.sale_report.store') }} : <strong>{{ $saleReport->store->name }}</strong></p>
    <p>{{ __('messages.emails.sale_report.total_amount') }} : <strong>{{ number_format($saleReport->total_amount_theoretical, 2) }} $</strong></p>

    <p>{{ __('messages.emails.sale_report.regards') }}</p>
    <p>{{ __('messages.emails.sale_report.team') }} {{ config('app.name') }}</p>
</body>
</html>
