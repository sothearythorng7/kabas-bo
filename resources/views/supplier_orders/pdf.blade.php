<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order #{{ $order->id }} - {{ $supplier->name }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
        }
        .header {
            margin-bottom: 20px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 10px 0;
        }
        h1 .supplier-name {
            background-color: #ffeb3b;
            padding: 2px 6px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            color: #fff;
            border-radius: 4px;
            font-size: 11px;
        }
        .badge-warning { background-color: #ff9800; }
        .badge-info { background-color: #2196f3; }
        .badge-success { background-color: #4caf50; }
        .badge-secondary { background-color: #757575; }
        .info-line {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .total-label {
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order #{{ $order->id }} - <span class="supplier-name">{{ $supplier->name }}</span></h1>

        <p class="info-line"><strong>Status:</strong>
            @if($order->status === 'pending')
                <span class="badge badge-secondary">Pending</span>
            @elseif($order->status === 'waiting_reception')
                <span class="badge badge-warning">Waiting reception</span>
            @elseif($order->status === 'received')
                <span class="badge badge-success">Received</span>
            @else
                <span class="badge badge-info">{{ ucfirst($order->status) }}</span>
            @endif
        </p>

        <p class="info-line"><strong>Created At:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
    </div>

    @if($order->order_type === 'raw_material')
    {{-- Raw Materials Order --}}
    @php
        $totalQtyOrdered = 0;
        $totalQtyReceived = 0;
        $totalAmount = 0;
    @endphp
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Unit</th>
                <th>Purchase Price</th>
                <th>Qty ordered</th>
                <th>Qty received</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->rawMaterials as $material)
                @php
                    $qtyOrdered = $material->pivot->quantity_ordered ?? 0;
                    $qtyReceived = $material->pivot->quantity_received ?? 0;
                    $purchasePrice = $material->pivot->purchase_price ?? 0;
                    $lineTotal = $qtyOrdered * $purchasePrice;
                    $totalQtyOrdered += $qtyOrdered;
                    $totalQtyReceived += $qtyReceived;
                    $totalAmount += $lineTotal;
                @endphp
                <tr>
                    <td>{{ $material->name }}</td>
                    <td>{{ $material->unit ?? '-' }}</td>
                    <td>$ {{ number_format($purchasePrice, 2) }}</td>
                    <td>{{ number_format($qtyOrdered, 0) }}</td>
                    <td>{{ $qtyReceived > 0 ? number_format($qtyReceived, 0) : '-' }}</td>
                    <td class="text-right">$ {{ number_format($lineTotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="total-label">TOTAL</td>
                <td class="text-center">{{ number_format($totalQtyOrdered, 0) }}</td>
                <td class="text-center">{{ $totalQtyReceived > 0 ? number_format($totalQtyReceived, 0) : '-' }}</td>
                <td class="text-right">$ {{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @else
    {{-- Products Order --}}
    @php
        $totalQtyOrdered = 0;
        $totalQtyReceived = 0;
        $totalAmount = 0;
    @endphp
    <table>
        <thead>
            <tr>
                <th>EAN</th>
                <th>Name</th>
                <th>Brand</th>
                <th>Purchase Price</th>
                <th>Price</th>
                <th>Qty ordered</th>
                <th>Qty received</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->products as $product)
                @php
                    $qtyOrdered = $product->pivot->quantity_ordered ?? 0;
                    $qtyReceived = $product->pivot->quantity_received ?? 0;
                    $purchasePrice = $product->pivot->purchase_price ?? 0;
                    $lineTotal = $qtyOrdered * $purchasePrice;
                    $totalQtyOrdered += $qtyOrdered;
                    $totalQtyReceived += $qtyReceived;
                    $totalAmount += $lineTotal;
                @endphp
                <tr>
                    <td>{{ $product->ean }}</td>
                    <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                    <td>{{ $product->brand?->name ?? '-' }}</td>
                    <td>$ {{ number_format($purchasePrice, 2) }}</td>
                    <td>$ {{ number_format($product->price, 2) }}</td>
                    <td>{{ $qtyOrdered }}</td>
                    <td>{{ $qtyReceived > 0 ? $qtyReceived : '-' }}</td>
                    <td class="text-right">$ {{ number_format($lineTotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="total-label">TOTAL</td>
                <td class="text-center">{{ $totalQtyOrdered }}</td>
                <td class="text-center">{{ $totalQtyReceived > 0 ? $totalQtyReceived : '-' }}</td>
                <td class="text-right">$ {{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif
</body>
</html>
