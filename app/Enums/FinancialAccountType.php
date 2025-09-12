<?php

namespace App\Enums;

enum FinancialAccountType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EXPENSE = 'expense';
    case REVENUE = 'revenue';

    /**
     * Libellé affiché dans les vues (traduction possible)
     */
    public function label(): string
    {
        return match($this) {
            self::ASSET => __('messages.financial.accounts.types.asset'),
            self::LIABILITY => __('messages.financial.accounts.types.liability'),
            self::EXPENSE => __('messages.financial.accounts.types.expense'),
            self::REVENUE => __('messages.financial.accounts.types.revenue'),
        };
    }

    /**
     * Tableau des valeurs pour validation et migration
     */
    public static function options(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
