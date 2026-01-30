<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Note #{{ $delivery->id }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
            color: #333;
        }
        .header {
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border: none;
        }
        .header-table td {
            border: none;
            vertical-align: top;
        }
        .logo {
            width: 120px;
        }
        .company-info {
            font-size: 10px;
            line-height: 1.4;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #c00;
        }
        .document-title {
            text-align: right;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .document-date {
            text-align: right;
            font-size: 12px;
            margin-top: 5px;
        }
        .bill-to {
            margin: 20px 0;
        }
        .bill-to-label {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .bill-to-content {
            font-size: 11px;
            line-height: 1.4;
        }
        .bill-to-content strong {
            font-weight: bold;
        }
        .object-line {
            margin: 15px 0;
            font-size: 11px;
        }
        .object-line strong {
            font-weight: bold;
        }
        table.products {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.products th {
            background: #f5f5f5;
            border-bottom: 2px solid #333;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
        }
        table.products th.right,
        table.products td.right {
            text-align: right;
        }
        table.products th.center,
        table.products td.center {
            text-align: center;
        }
        table.products td {
            border-bottom: 1px solid #ddd;
            padding: 6px 5px;
        }
        table.products tr:last-child td {
            border-bottom: 2px solid #333;
        }
        .totals-row td {
            font-weight: bold;
            padding-top: 10px;
        }
        .signatures {
            margin-top: 80px;
            width: 100%;
        }
        .signatures td {
            width: 50%;
            border: none;
            padding-top: 50px;
            border-top: 1px solid #333;
            text-align: center;
            font-weight: bold;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <img src="{{ public_path('images/kabas_logo.png') }}" class="logo" alt="Kabas">
                    <div class="company-info">
                        <div class="company-name">Kabas Concept Store</div>
                        <div>contact@kabasconceptstore.com</div>
                        <div>+855 (0)12 345 678</div>
                        <div>Phnom Penh, Cambodia</div>
                    </div>
                </td>
                <td style="width: 40%;">
                    <div class="document-title">DELIVERY NOTE</div>
                    <div class="document-date">{{ $delivery->created_at->format('j/n/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Bill To --}}
    <div class="bill-to">
        <div class="bill-to-label">DELIVER TO</div>
        <div class="bill-to-content">
            <strong>{{ $reseller->name }}</strong><br>
            @if(isset($reseller->contacts) && $reseller->contacts->count() > 0)
                @php $contact = $reseller->contacts->first(); @endphp
                @if($contact->address){{ $contact->address }}<br>@endif
                @if($contact->email){{ $contact->email }}<br>@endif
                @if($contact->phone){{ $contact->phone }}@endif
            @endif
        </div>
    </div>

    {{-- Object --}}
    <div class="object-line">
        <strong>Object:</strong> Delivery #{{ $delivery->id }} - {{ $delivery->created_at->format('F Y') }}
    </div>

    {{-- Products Table --}}
    @php
        $totalQty = 0;
        $totalAmount = 0;
        $itemNumber = 0;
    @endphp

    <table class="products">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 120px;">Barcode</th>
                <th>Product Description</th>
                <th class="center" style="width: 50px;">QTY</th>
                <th class="right" style="width: 80px;">Unit Price</th>
                <th class="right" style="width: 80px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->products as $product)
                @php
                    $itemNumber++;
                    $qty = $product->pivot->quantity;
                    $unitPrice = $product->pivot->unit_price;
                    $amount = $qty * $unitPrice;
                    $totalQty += $qty;
                    $totalAmount += $amount;
                    $productName = $product->name[app()->getLocale()] ?? reset($product->name);
                @endphp
                <tr>
                    <td>{{ $itemNumber }}</td>
                    <td>{{ $product->ean }}</td>
                    <td>{{ $productName }}</td>
                    <td class="center">{{ $qty }}</td>
                    <td class="right">$ {{ number_format($unitPrice, 2) }}</td>
                    <td class="right">$ {{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="products" style="margin-top: 0; border: none;">
        <tr class="totals-row">
            <td style="width: 30px; border: none;"></td>
            <td style="width: 120px; border: none;"></td>
            <td style="border: none; text-align: right;"><strong>TOTAL QTY</strong></td>
            <td class="center" style="width: 50px; border: none;"><strong>{{ $totalQty }}</strong></td>
            <td class="right" style="width: 80px; border: none;"><strong>TOTAL</strong></td>
            <td class="right" style="width: 80px; border: none;"><strong>$ {{ number_format($totalAmount, 2) }}</strong></td>
        </tr>
    </table>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>Seller's Signature & Name</td>
            <td>Buyer's Signature & Name</td>
        </tr>
    </table>
</body>
</html>
