@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.sale_report.pdf_title') }} #{{ $saleReport->id }}</h1>
    <p><strong>{{ __('messages.menu.suppliers') }}:</strong> {{ $supplier->name }}</p>
    <p><strong>{{ __('messages.store.name') }}:</strong> {{ $saleReport->store->name }}</p>
    <p><strong>{{ __('messages.sale_report.period') }}:</strong> {{ $saleReport->period_start->format('d/m/Y') }} - {{ $saleReport->period_end->format('d/m/Y') }}</p>

    <div class="mt-3 mb-3">
        @if($saleReport->report_file_path)
        <a href="{{ Storage::url($saleReport->report_file_path) }}" target="_blank" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-pdf"></i> {{ __('messages.btn.download_pdf') }}
        </a>
        <a href="{{ route('sale-reports.send', [$supplier, $saleReport]) }}" class="btn btn-outline-success">
            <i class="bi bi-send"></i> {{ __('messages.sale_report.send_report') }}
        </a>
        @endif
        <form action="{{ route('sale-reports.regeneratePdf', [$supplier, $saleReport]) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-warning">
                <i class="bi bi-arrow-clockwise"></i> Regenerate PDF
            </button>
        </form>
    </div>

    @if($saleReport->sent_at)
        <div class="alert alert-info mt-2">
            {{ __('messages.sent_at') }} {{ $saleReport->sent_at->format('d/m/Y H:i') }} {{ __('messages.to') }} {{ $saleReport->sent_to }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>{{ __('messages.product.barcode') }}</th>
                    <th>{{ __('messages.product.name') }}</th>
                    <th class="text-center">{{ __('messages.sale_report.old_stock') }}</th>
                    <th class="text-center">{{ __('messages.sale_report.refill') }}</th>
                    <th class="text-center">{{ __('messages.sale_report.returns') }}</th>
                    <th class="text-center">{{ __('messages.sale_report.stock_on_hand') }}</th>
                    <th class="text-center">{{ __('messages.sale_report.quantity_sold') }}</th>
                    <th class="text-end">{{ __('messages.sale_report.cost_price') }}</th>
                    <th class="text-end">{{ __('messages.sale_report.selling_price') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalOldStock = 0;
                    $totalRefill = 0;
                    $totalReturns = 0;
                    $totalStockOnHand = 0;
                    $totalQuantitySold = 0;
                    $totalPayAmount = 0;
                    $totalSaleAmount = 0;
                @endphp
                @foreach($saleReport->items as $item)
                    @php
                        $totalOldStock += $item->old_stock;
                        $totalRefill += $item->refill;
                        $totalReturns += $item->returns;
                        $totalStockOnHand += $item->stock_on_hand;
                        $totalQuantitySold += $item->quantity_sold;
                        $totalPayAmount += $item->total;
                        $totalSaleAmount += $item->selling_price;
                    @endphp
                    <tr>
                        <td>{{ $item->product->ean }}</td>
                        <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                        <td class="text-center">{{ $item->old_stock }}</td>
                        <td class="text-center">{{ $item->refill }}</td>
                        <td class="text-center">{{ $item->returns }}</td>
                        <td class="text-center">{{ $item->stock_on_hand }}</td>
                        <td class="text-center"><strong>{{ $item->quantity_sold }}</strong></td>
                        <td class="text-end">$ {{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end">$ {{ number_format($item->selling_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <td colspan="2"><strong>Total</strong></td>
                    <td class="text-center"><strong>{{ $totalOldStock }}</strong></td>
                    <td class="text-center"><strong>{{ $totalRefill }}</strong></td>
                    <td class="text-center"><strong>{{ $totalReturns }}</strong></td>
                    <td class="text-center"><strong>{{ $totalStockOnHand }}</strong></td>
                    <td class="text-center"><strong>{{ $totalQuantitySold }}</strong></td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Summary --}}
    <div class="card mt-3" style="max-width: 350px;">
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr>
                    <td><strong><em>{{ __('messages.sale_report.total_sale_amount') }}</em></strong></td>
                    <td class="text-end"><strong>$</strong></td>
                    <td class="text-end" style="width: 80px;"><strong>{{ number_format($totalSaleAmount, 2) }}</strong></td>
                </tr>
                <tr>
                    <td><strong><em>{{ __('messages.sale_report.total_pay_amount') }}</em></strong></td>
                    <td class="text-end"><strong>$</strong></td>
                    <td class="text-end"><strong>{{ number_format($totalPayAmount, 2) }}</strong></td>
                </tr>
                <tr class="table-success">
                    <td><strong><em>{{ __('messages.sale_report.net_profit') }}</em></strong></td>
                    <td class="text-end"><strong>$</strong></td>
                    <td class="text-end"><strong>{{ number_format($totalSaleAmount - $totalPayAmount, 2) }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('suppliers.edit', $supplier) }}#sales-reports" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>
</div>
@endsection
