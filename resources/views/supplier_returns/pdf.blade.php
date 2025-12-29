<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.supplier_return.pdf_title') }} #{{ $return->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 10px; }
        .header { margin-bottom: 20px; }
        .header p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background: #e0e0e0; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; }
        .signature-section { margin-top: 50px; }
        .signature-box { display: inline-block; width: 45%; border-top: 1px solid #000; padding-top: 5px; margin-top: 50px; }
    </style>
</head>
<body>
    <h1>{{ __('messages.supplier_return.pdf_title') }} #{{ $return->id }}</h1>

    <div class="header">
        <p><strong>{{ __('messages.menu.suppliers') }}:</strong> {{ $return->supplier->name }}</p>
        <p><strong>{{ __('messages.store.name') }}:</strong> {{ $return->store->name }}</p>
        <p><strong>{{ __('messages.common.date') }}:</strong> {{ $return->created_at->format('d/m/Y') }}</p>
        @if($return->validated_at)
            <p><strong>{{ __('messages.supplier_return.validated_at') }}:</strong> {{ $return->validated_at->format('d/m/Y H:i') }}</p>
        @endif
        @if($return->notes)
            <p><strong>{{ __('messages.supplier_return.notes') }}:</strong> {{ $return->notes }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>EAN</th>
                <th>{{ __('messages.product.name') }}</th>
                <th>{{ __('messages.product.brand_label') }}</th>
                <th class="text-center">{{ __('messages.supplier_return.quantity') }}</th>
                <th class="text-right">{{ __('messages.supplier.price') }}</th>
                <th class="text-right">{{ __('messages.supplier_return.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($return->items as $item)
                <tr>
                    <td>{{ $item->product->ean ?? '-' }}</td>
                    <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                    <td>{{ $item->product->brand?->name ?? '-' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3"><strong>{{ __('messages.supplier_return.total') }}</strong></td>
                <td class="text-center"><strong>{{ $return->total_quantity }}</strong></td>
                <td></td>
                <td class="text-right"><strong>${{ number_format($return->total_value, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-section">
        <table style="border: none; width: 100%;">
            <tr style="border: none;">
                <td style="border: none; width: 50%; vertical-align: top;">
                    <p><strong>{{ __('messages.supplier_return.store_signature') }}:</strong></p>
                    <br><br><br>
                    <p>_________________________________</p>
                    <p>{{ __('messages.common.date') }}: _______________</p>
                </td>
                <td style="border: none; width: 50%; vertical-align: top;">
                    <p><strong>{{ __('messages.supplier_return.supplier_signature') }}:</strong></p>
                    <br><br><br>
                    <p>_________________________________</p>
                    <p>{{ __('messages.common.date') }}: _______________</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>{{ __('messages.supplier_return.pdf_generated_at') }}: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
