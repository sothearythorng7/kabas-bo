<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoiceNumber }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 20mm 25mm 20mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            line-height: 1.4;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .header-left {
            display: table-cell;
            width: 65%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 35%;
            vertical-align: top;
            text-align: right;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .company-info {
            font-size: 10px;
            line-height: 1.6;
        }
        .logo {
            height: 65px;
        }

        /* Title */
        .invoice-title {
            text-align: center;
            margin: 15px 0 20px 0;
        }
        .invoice-title .english {
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
        }

        /* Customer & Invoice meta */
        .meta-section {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .meta-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .meta-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .meta-label {
            font-size: 10px;
            color: #555;
        }

        /* Items table */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        table.items th {
            background: #f5f5f5;
            border: 1px solid #999;
            padding: 8px 10px;
            font-size: 11px;
            text-align: center;
        }
        table.items td {
            border: 1px solid #999;
            padding: 7px 10px;
            font-size: 11px;
        }
        table.items td.num {
            text-align: center;
        }
        table.items td.money {
            text-align: right;
            white-space: nowrap;
        }
        table.items td.desc {
            text-align: left;
        }

        /* Totals */
        table.totals {
            width: 100%;
            border-collapse: collapse;
        }
        table.totals td {
            padding: 5px 10px;
            font-size: 11px;
            border: 1px solid #999;
        }
        table.totals td.label {
            text-align: right;
            font-weight: bold;
        }
        table.totals td.amount {
            text-align: right;
            width: 150px;
            white-space: nowrap;
        }

        /* Bank info */
        .bank-info {
            margin-top: 25px;
            font-size: 10px;
            line-height: 1.6;
        }
        .bank-info strong {
            font-size: 11px;
        }

        /* Signatures */
        .signatures {
            display: table;
            width: 100%;
            margin-top: 80px;
        }
        .sig-left, .sig-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }
        .sig-line {
            border-top: 1px solid #333;
            width: 180px;
            display: inline-block;
            margin-bottom: 5px;
        }
        .sig-label {
            font-size: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="company-name">KABAS</div>
            <div class="company-info">
                Adress: #65, STREET 178, Phnom Penh.<br>
                <strong>FLEURY ALEXIS</strong><br>
                Tell: 855(0)69 439 094 ; E-mail: alexis.fleury9@gmail.com
            </div>
        </div>
        <div class="header-right">
            @if(file_exists(public_path('images/kabas_logo.png')))
                <img src="{{ public_path('images/kabas_logo.png') }}" class="logo">
            @endif
        </div>
    </div>

    <!-- Title -->
    <div class="invoice-title">
        <div class="english">INVOICE</div>
    </div>

    <!-- Customer & Invoice number -->
    <div class="meta-section">
        <div class="meta-left">
            <strong>CUSTOMERS :</strong> {{ $order->shipping_full_name }}<br>
            @if($order->shipping_address_line1)
                <strong>ADDRESS:</strong> {{ $order->shipping_full_address }}
            @else
                <strong>ADDRESS:</strong>
            @endif
        </div>
        <div class="meta-right">
            INV No: <strong>{{ $invoiceNumber }}</strong><br>
            Date: <strong>{{ $order->created_at->format('d-M-Y') }}</strong>
        </div>
    </div>

    <!-- Items table -->
    <table class="items">
        <thead>
            <tr>
                <th style="width:30px;">No.</th>
                <th>Product Description</th>
                <th style="width:80px;">Quantity</th>
                <th style="width:90px;">Unit Price</th>
                <th style="width:110px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNum = 1; @endphp

            {{-- Product items --}}
            @foreach($productItems as $item)
            <tr>
                <td class="num">{{ $rowNum++ }}</td>
                <td class="desc">{{ $item->product_name }}</td>
                <td class="num">{{ $item->quantity }} Pcs</td>
                <td class="money">$ {{ number_format($item->unit_price, 2) }}</td>
                <td class="money">$ {{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach

            {{-- Paid options --}}
            @foreach($optionItems as $item)
            <tr>
                <td class="num">{{ $rowNum++ }}</td>
                <td class="desc">{{ $item->product_name }}</td>
                <td class="num">1</td>
                <td class="money">$ {{ number_format($item->unit_price, 2) }}</td>
                <td class="money">$ {{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals">
        @if($order->discount > 0)
        {{-- Show subtotal before discount --}}
        <tr>
            <td class="label">Subtotal</td>
            <td class="amount">$ {{ number_format($order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Discount</td>
            <td class="amount">- $ {{ number_format($order->discount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">{{ $order->discount > 0 ? 'Total After Discount' : 'Total' }}</td>
            <td class="amount"><strong>$ {{ number_format($order->total, 2) }}</strong></td>
        </tr>
        @if($order->deposit_amount > 0)
            @php
                $depositPercent = $order->total > 0 ? round(($order->deposit_amount / $order->total) * 100) : 0;
                $remaining = max(0, $order->total - $order->deposit_amount);
                $remainingPercent = 100 - $depositPercent;
            @endphp
            <tr>
                <td class="label">Deposit {{ $depositPercent }}%</td>
                <td class="amount">$ {{ number_format($order->deposit_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Final Payment {{ $remainingPercent }}%</td>
                <td class="amount"><strong>$ {{ number_format($remaining, 2) }}</strong></td>
            </tr>
        @endif
    </table>

    <!-- Bank info -->
    <div class="bank-info">
        <strong>The payment info to: ABA BANK</strong><br>
        &nbsp;&nbsp;&nbsp;&nbsp;SWIFT code: <strong>ABAAKHPP</strong><br>
        Account Name: <strong>FLEURY ALEXIS FREDERIC</strong><br>
        Account Number: <strong>002 791 816</strong>
        <br><br>
        <strong>KABAS</strong>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="sig-left">
            <div class="sig-line"></div><br>
            <span class="sig-label">Seller's Signature & Name</span>
        </div>
        <div class="sig-right">
            <div class="sig-line"></div><br>
            <span class="sig-label">Buyer's Signature & Name</span>
        </div>
    </div>
</body>
</html>
