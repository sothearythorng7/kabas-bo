<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.sale_report.pdf_title') }} #{{ $saleReport->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>{{ __('messages.sale_report.pdf_title') }} #{{ $saleReport->id }}</h1>
    <p><strong>{{ __('messages.menu.suppliers') }} :</strong> {{ $saleReport->supplier->name }}</p>
    <p><strong>{{ __('messages.store.name') }} :</strong> {{ $saleReport->store->name }}</p>
    <p><strong>{{ __('messages.sale_report.period') }} :</strong> {{ $saleReport->period_start->format('d/m/Y') }} - {{ $saleReport->period_end->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>EAN</th>
                <th>{{ __('messages.product.name') }}</th>
                <th>{{ __('messages.sale_report.quantity_sold') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($saleReport->items as $item)
                <tr>
                    <td>{{ $item->product->ean }}</td>
                    <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                    <td>{{ $item->quantity_sold }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
