<?php

namespace App\Enums;

enum InvoiceType: string
{
    case GENERAL_EXPENSE = 'general_expense';
    case SUPPLY_PURCHASE = 'supply_purchase';
    case PRODUCT_PURCHASE = 'product_purchase';
    case RAW_MATERIAL_PURCHASE = 'raw_material_purchase';

    public function label(): string
    {
        return __('messages.warehouse_invoices.enums.type.' . $this->value);
    }

    public static function options(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
