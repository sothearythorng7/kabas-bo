<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $reseller->name }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            margin: 0;
            color: #333;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }
        .logo {
            height: 60px;
            margin-bottom: 5px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #c00;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 10px;
            line-height: 1.5;
        }
        .company-info a {
            color: #c00;
            text-decoration: underline;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #c00;
        }
        .invoice-date {
            font-size: 12px;
            margin-top: 5px;
        }
        .bill-to {
            margin: 20px 0;
        }
        .bill-to-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .bill-to-content {
            line-height: 1.5;
        }
        .bill-to-content strong {
            font-weight: bold;
        }
        .object {
            margin: 15px 0;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #c00;
            color: #fff;
            padding: 8px 5px;
            text-align: left;
            font-size: 10px;
        }
        th.text-center {
            text-align: center;
        }
        th.text-right {
            text-align: right;
        }
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        td.text-center {
            text-align: center;
        }
        td.text-right {
            text-align: right;
        }
        .total-row td {
            font-weight: bold;
            border-top: 2px solid #333;
            border-bottom: none;
            padding-top: 10px;
        }
        .payment-section {
            margin-top: 40px;
        }
        .payment-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .payment-info {
            line-height: 1.6;
        }
        .signatures {
            display: table;
            width: 100%;
            margin-top: 60px;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-weight: bold;
            font-size: 10px;
        }
    </style>
</head>
<body>
    {{-- Header: Kabas info (seller) --}}
    <div class="header">
        <div class="header-left">
            @php
                $logoPath = public_path('images/kabas_logo.png');
                $logoData = base64_encode(file_get_contents($logoPath));
            @endphp
            <img src="data:image/png;base64,{{ $logoData }}" class="logo" alt="Kabas">
            <div class="company-info">
                <a href="mailto:contact@kabasconceptstore.com">contact@kabasconceptstore.com</a><br>
                <strong>015 656 122</strong><br>
                Address: 65 Street 178, Phnom Penh 12302
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-date">{{ $report->created_at->format('n/j/Y') }}</div>
        </div>
    </div>

    {{-- Bill To: Reseller info (buyer) --}}
    <div class="bill-to">
        <div class="bill-to-title">BILL TO</div>
        <div class="bill-to-content">
            <strong>{{ $reseller->name }}</strong><br>
            @if($reseller->address)
                {{ $reseller->address }}<br>
            @endif
            @if($reseller->address2)
                {{ $reseller->address2 }}<br>
            @endif
            @if($reseller->city || $reseller->postal_code)
                {{ $reseller->postal_code }} {{ $reseller->city }}<br>
            @endif
            @if($reseller->country)
                {{ $reseller->country }}<br>
            @endif
            @if($reseller->phone)
                {{ $reseller->phone }}<br>
            @endif
            @if($reseller->email)
                {{ $reseller->email }}<br>
            @endif
            @if($reseller->tax_id)
                <strong>Tax ID: {{ $reseller->tax_id }}</strong>
            @endif
        </div>
    </div>

    {{-- Object --}}
    <div class="object">
        Object: SALES
        @if($report->start_date && $report->end_date)
            {{ strtoupper($report->start_date->format('F')) }}-{{ $report->end_date->format('Y') }}
        @else
            {{ strtoupper($report->created_at->format('F-Y')) }}
        @endif
    </div>

    {{-- Products Table --}}
    @php
        $totalQty = 0;
        $totalAmount = 0;
    @endphp
    <table>
        <thead>
            <tr>
                <th style="width: 25px;">#</th>
                <th>Barcode</th>
                <th>Product</th>
                <th class="text-center">QTY Sold</th>
                <th class="text-right">Price</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNum = 0; @endphp
            @foreach($report->items as $item)
                @if($item->quantity_sold <= 0) @continue @endif
                @php
                    $rowNum++;
                    $qty = $item->quantity_sold;
                    $unitPrice = $item->unit_price;
                    $amount = $qty * $unitPrice;
                    $totalQty += $qty;
                    $totalAmount += $amount;
                @endphp
                <tr>
                    <td>{{ $rowNum }}</td>
                    <td>{{ $item->product->ean ?? '-' }}</td>
                    <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-right">$ {{ number_format($unitPrice, 2) }}</td>
                    <td class="text-right">$ {{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL QTY</td>
                <td class="text-center">{{ $totalQty }}</td>
                <td class="text-right">TOTAL</td>
                <td class="text-right">$ {{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Payment Section --}}
    <div class="payment-section">
        <div class="payment-title">Payment</div>
        <div class="payment-info">
            The payment info to: <strong>ABA BANK</strong><br>
            Beneficiary: <strong>FLEURY ALEXIS FREDERIC</strong><br>
            Account: <strong>002 791 816</strong>
        </div>
    </div>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">Seller's Signature & Name</div>
        </div>
        <div class="signature-box" style="text-align: right;">
            <div class="signature-line">Buyer's Signature & Name</div>
        </div>
    </div>
</body>
</html>
