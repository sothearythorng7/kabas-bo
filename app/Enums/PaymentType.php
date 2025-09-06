<?php

namespace App\Enums;

enum PaymentType: string
{
    case TRANSFER = 'bank_transfer';
    case CASH = 'cash';

    public function label(): string
    {
        return __('messages.warehouse_invoices.enums.payment_type.' . $this->value);
    }

    public static function options(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
