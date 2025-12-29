<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoiceNumber }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .header-left, .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            text-align: right;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 14px;
            color: #7f8c8d;
        }
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .address-box {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .address-box h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        .address-box p {
            margin: 3px 0;
        }
        .spacer {
            display: table-cell;
            width: 4%;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.items th {
            background: #2c3e50;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        table.items th:last-child,
        table.items td:last-child {
            text-align: right;
        }
        table.items td {
            padding: 10px 8px;
            border-bottom: 1px solid #ecf0f1;
        }
        table.items tr:nth-child(even) {
            background: #f8f9fa;
        }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 8px;
        }
        .totals tr.total {
            background: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .totals tr.total td:last-child {
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            text-align: center;
            color: #7f8c8d;
            font-size: 10px;
        }
        .meta-info {
            margin-bottom: 20px;
        }
        .meta-info table {
            width: 100%;
        }
        .meta-info td {
            padding: 5px 0;
        }
        .meta-info td:first-child {
            color: #7f8c8d;
            width: 150px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">KABAS</div>
            <p>Concept Store</p>
        </div>
        <div class="header-right">
            <div class="invoice-title">{{ strtoupper($invoiceType ?? 'FACTURE INTERNE') }}</div>
            <div class="invoice-number">{{ $invoiceNumber }}</div>
            <p style="margin-top: 10px;">Date: {{ $movement->created_at->format('d/m/Y') }}</p>
        </div>
    </div>

    <div class="addresses">
        <div class="address-box">
            <h3>Expéditeur</h3>
            <p><strong>{{ $movement->fromStore->name }}</strong></p>
            @if($movement->fromStore->address)
            <p>{{ $movement->fromStore->address }}</p>
            @endif
            @if($movement->fromStore->phone)
            <p>Tél: {{ $movement->fromStore->phone }}</p>
            @endif
        </div>
        <div class="spacer"></div>
        <div class="address-box">
            <h3>Destinataire</h3>
            <p><strong>{{ $movement->toStore->name }}</strong></p>
            @if($movement->toStore->address)
            <p>{{ $movement->toStore->address }}</p>
            @endif
            @if($movement->toStore->phone)
            <p>Tél: {{ $movement->toStore->phone }}</p>
            @endif
        </div>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td>Mouvement de stock #:</td>
                <td>{{ $movement->id }}</td>
            </tr>
            <tr>
                <td>Créé par:</td>
                <td>{{ $movement->user->name ?? '-' }}</td>
            </tr>
            @if($movement->note)
            <tr>
                <td>Note:</td>
                <td>{{ $movement->note }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 80px;">EAN</th>
                <th>Produit</th>
                <th style="width: 60px; text-align: center;">Qté</th>
                <th style="width: 80px;">Prix unit.</th>
                <th style="width: 100px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movement->items as $item)
            <tr>
                <td>{{ $item->product->ean ?? '-' }}</td>
                <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">{{ number_format($item->unit_price ?? 0, 2) }} $</td>
                <td style="text-align: right;">{{ number_format($item->quantity * ($item->unit_price ?? 0), 2) }} $</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Sous-total:</td>
                <td style="text-align: right;">{{ number_format($totalAmount, 2) }} $</td>
            </tr>
            <tr class="total">
                <td>TOTAL</td>
                <td>{{ number_format($totalAmount, 2) }} USD</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Facture générée automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>KABAS Concept Store - Document interne</p>
    </div>
</body>
</html>
