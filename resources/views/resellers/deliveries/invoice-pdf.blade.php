<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $delivery->id }} - {{ $reseller->name }}</title>
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
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .document-subtitle {
            font-size: 14px;
            color: #f90;
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            background: #f90;
            color: #fff;
            border-radius: 3px;
            font-size: 10px;
            margin-top: 5px;
        }
        .meta-info {
            margin: 15px 0;
            font-size: 11px;
        }
        table.products {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table.products th {
            background: #f5f5f5;
            border-bottom: 2px solid #333;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        table.products th.right,
        table.products td.right {
            text-align: right;
        }
        table.products th.center,
        table.products td.center {
            text-align: center;
        }
        table.products th.highlight {
            background: #f90;
            color: #fff;
        }
        table.products td {
            border-bottom: 1px solid #ddd;
            padding: 6px 5px;
            font-size: 10px;
        }
        .totals-row {
            background: #f5f5f5;
            font-weight: bold;
        }
        .totals-row td {
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .footer-note {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <div class="document-title">Order #{{ $delivery->id }} - <span style="color: #f90;">{{ strtoupper($reseller->name) }}</span></div>
                    <div class="status-badge">{{ ucfirst(str_replace('_', ' ', $delivery->status)) }}</div>
                    <div class="meta-info">
                        <strong>Created At:</strong> {{ $delivery->created_at->format('d/m/Y H:i') }}
                    </div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <img src="{{ public_path('images/kabas_logo.png') }}" class="logo" alt="Kabas">
                </td>
            </tr>
        </table>
    </div>

    {{-- Products Table --}}
    @php
        $totalQty = 0;
        $grandTotal = 0;
    @endphp

    <table class="products">
        <thead>
            <tr>
                <th style="width: 120px;">EAN</th>
                <th>Name</th>
                <th style="width: 70px;">Brand</th>
                <th class="right" style="width: 70px;">Purchase Price</th>
                <th class="right highlight" style="width: 60px;">Price</th>
                <th class="center" style="width: 60px;">Qty ordered</th>
                <th class="center" style="width: 60px;">Qty received</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->products as $product)
                @php
                    $qty = $product->pivot->quantity;
                    $unitPrice = $product->pivot->unit_price;
                    $totalQty += $qty;
                    $lineTotal = $qty * $unitPrice;
                    $grandTotal += $lineTotal;
                    $productName = $product->name[app()->getLocale()] ?? reset($product->name);
                    // Get purchase price from suppliers pivot if available
                    $purchasePrice = $product->suppliers->first()?->pivot?->purchase_price ?? 0;
                @endphp
                <tr>
                    <td>{{ $product->ean }}</td>
                    <td>{{ $productName }}</td>
                    <td>{{ $product->brand?->name ?? 'Kabas' }}</td>
                    <td class="right">{{ number_format($purchasePrice, 2) }}</td>
                    <td class="right" style="background: #fff8e6;">{{ number_format($unitPrice, 2) }}</td>
                    <td class="center">{{ $qty }}</td>
                    <td class="center">-</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="4" style="text-align: right;"><strong>TOTAL</strong></td>
                <td class="right" style="background: #fff8e6;"></td>
                <td class="center"><strong>TOTAL QTY</strong></td>
                <td class="center"><strong>TOTAL QTY RECEIVED</strong></td>
            </tr>
            <tr class="totals-row">
                <td colspan="4"></td>
                <td class="right" style="background: #fff8e6;"><strong>$ {{ number_format($grandTotal, 2) }}</strong></td>
                <td class="center"><strong>{{ $totalQty }}</strong></td>
                <td class="center">-</td>
            </tr>
        </tfoot>
    </table>

    @if($delivery->shipping_cost > 0)
    <table class="products" style="margin-top: 10px; width: 300px; margin-left: auto;">
        <tr>
            <td style="text-align: right; border: none;">Shipping Cost:</td>
            <td style="text-align: right; border: none; width: 100px;">$ {{ number_format($delivery->shipping_cost, 2) }}</td>
        </tr>
        <tr style="font-weight: bold;">
            <td style="text-align: right; border: none;">Grand Total:</td>
            <td style="text-align: right; border: none;">$ {{ number_format($grandTotal + $delivery->shipping_cost, 2) }}</td>
        </tr>
    </table>
    @endif

    <div class="footer-note">
        Generated on {{ now()->format('d/m/Y H:i') }} - Kabas Concept Store
    </div>
</body>
</html>
