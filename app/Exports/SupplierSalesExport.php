<?php

namespace App\Exports;

use App\Models\SaleItem;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierSalesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected Supplier $supplier;
    protected string $startDate;
    protected string $endDate;
    protected ?int $storeId;
    protected string $locale;

    public function __construct(Supplier $supplier, string $startDate, string $endDate, ?int $storeId = null)
    {
        $this->supplier = $supplier;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->storeId = $storeId;
        $this->locale = app()->getLocale();
    }

    public function collection()
    {
        $productIds = $this->supplier->products()->pluck('products.id')->toArray();

        if (empty($productIds)) {
            return collect();
        }

        return SaleItem::whereIn('product_id', $productIds)
            ->whereNull('exchanged_at')
            ->whereHas('sale', function ($q) {
                $q->whereDate('created_at', '>=', $this->startDate)
                  ->whereDate('created_at', '<=', $this->endDate);
                if ($this->storeId) {
                    $q->where('store_id', $this->storeId);
                }
            })
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(quantity * price) as total_revenue')
            ->groupBy('product_id')
            ->with('product')
            ->get();
    }

    public function headings(): array
    {
        return [
            'EAN',
            __('messages.product.name'),
            __('messages.supplier.quantity_sold'),
            __('messages.supplier.unit_price_avg'),
            'Total',
        ];
    }

    public function map($item): array
    {
        $productName = $item->product->name[$this->locale] ?? reset($item->product->name);
        $avgPrice = $item->total_quantity > 0 ? $item->total_revenue / $item->total_quantity : 0;

        return [
            $item->product->ean ?? '-',
            $productName,
            $item->total_quantity,
            number_format($avgPrice, 2),
            number_format($item->total_revenue, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
