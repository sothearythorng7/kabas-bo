<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case TO_PAY = 'to_pay';
    case PAID = 'paid';
    case REIMBURSED = 'reimbursed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('messages.warehouse_invoices.enums.status.' . $this->value);
    }

    public static function options(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
