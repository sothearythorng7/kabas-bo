<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.sale_report.pdf_title') }} #{{ $saleReport->id }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
        }
        h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .header-info {
            margin-bottom: 15px;
        }
        .header-info p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        th {
            background: #d9d9d9;
            font-weight: bold;
            text-align: center;
        }
        td {
            text-align: center;
        }
        td.left {
            text-align: left;
        }
        td.right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
        }
        .summary-table {
            width: auto;
            margin-top: 20px;
            border: none;
        }
        .summary-table td {
            border: 1px solid #000;
            padding: 6px 12px;
            background: #d9d9d9;
        }
        .summary-table .label {
            font-style: italic;
            font-weight: bold;
            text-align: left;
        }
        .summary-table .currency {
            text-align: center;
            width: 30px;
        }
        .summary-table .amount {
            text-align: right;
            width: 80px;
        }
    </style>
</head>
<body>
    <h1>Sale report #{{ $saleReport->id }}</h1>
    <div class="header-info">
        <p>Supplier : {{ $saleReport->supplier->name }}</p>
        <p>Name : {{ $saleReport->store->name }}</p>
        <p>Period : {{ $saleReport->period_start->format('j/m/Y') }} - {{ $saleReport->period_end->format('j/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Barcode</th>
                <th>Product Name</th>
                <th>Old Stock</th>
                <th>Refill</th>
                <th>Stock on<br>Hand</th>
                <th>Quantity<br>Sold</th>
                <th>Cost Price</th>
                <th>Selling Price</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalOldStock = 0;
                $totalRefill = 0;
                $totalStockOnHand = 0;
                $totalQuantitySold = 0;
                $totalPayAmount = 0;
                $totalSaleAmount = 0;
            @endphp
            @foreach($saleReport->items as $item)
                @php
                    $totalOldStock += $item->old_stock;
                    $totalRefill += $item->refill;
                    $totalStockOnHand += $item->stock_on_hand;
                    $totalQuantitySold += $item->quantity_sold;
                    $totalPayAmount += $item->total;
                    $totalSaleAmount += $item->selling_price;
                @endphp
                <tr>
                    <td>{{ $item->product->ean }}</td>
                    <td class="left">{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                    <td>{{ $item->old_stock }}</td>
                    <td>{{ $item->refill }}</td>
                    <td>{{ $item->stock_on_hand }}</td>
                    <td><strong>{{ $item->quantity_sold }}</strong></td>
                    <td class="right">$ {{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">$ {{ number_format($item->selling_price, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2"><strong>Total</strong></td>
                <td><strong>{{ $totalOldStock }}</strong></td>
                <td><strong>{{ $totalRefill }}</strong></td>
                <td><strong>{{ $totalStockOnHand }}</strong></td>
                <td><strong>{{ $totalQuantitySold }}</strong></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="label">Total Sale Amount</td>
            <td class="currency">$</td>
            <td class="amount">{{ number_format($totalSaleAmount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total pay Amount</td>
            <td class="currency">$</td>
            <td class="amount">{{ number_format($totalPayAmount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Net Profit</td>
            <td class="currency">$</td>
            <td class="amount">{{ number_format($totalSaleAmount - $totalPayAmount, 2) }}</td>
        </tr>
    </table>
</body>
</html>
