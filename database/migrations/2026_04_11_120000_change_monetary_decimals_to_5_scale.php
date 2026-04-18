<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1 du plan "Migration 5 décimales".
 *
 * Convertit toutes les colonnes monétaires de decimal(P,2) vers decimal(P+3,5)
 * afin de gagner 3 chiffres de précision fractionnaire SANS réduire la plage
 * des entiers (ex: decimal(10,2) → decimal(13,5) conserve 8 chiffres entiers).
 *
 * Les colonnes non monétaires sont EXCLUES :
 *  - pourcentages : employee_commissions.percentage
 *  - quantités    : raw_material_stock_batches.quantity,
 *                   raw_material_stock_movements.quantity,
 *                   raw_materials.alert_quantity
 *  - congés       : leave_quotas.(annual_quota|carryover_days|monthly_accrual)
 *  - heures       : salary_adjustments.hours
 *  - GPS          : gps_positions.(battery_level|speed)
 *  - poids        : shipping_rates.(weight_from|weight_to)
 *
 * MySQL préserve les données existantes : les valeurs stockées en (P,2) sont
 * simplement paddées de zéros supplémentaires en (P+3,5).
 *
 * ATTENTION ROLLBACK : la migration down() retranche les 3 décimales ajoutées ;
 * toute valeur saisie entre-temps avec plus de 2 décimales sera tronquée.
 */
return new class extends Migration {
    /**
     * Liste des colonnes à modifier.
     * Format : [table, colonne, précision actuelle, nullable, default courant]
     */
    private array $columns = [
        // cart_items
        ['cart_items', 'price', 10, false, null],

        // cash_transactions & items
        ['cash_transaction_items', 'line_total', 15, false, null],
        ['cash_transaction_items', 'unit_price', 15, false, null],
        ['cash_transactions', 'total_amount', 15, false, null],

        // commissions (hors percentage)
        ['commission_calculations', 'base_amount', 12, false, null],
        ['commission_calculations', 'commission_amount', 10, false, null],

        // exchanges
        ['exchange_items', 'total_price', 10, false, null],
        ['exchange_items', 'unit_price', 10, false, null],
        ['exchanges', 'balance', 10, false, null],
        ['exchanges', 'new_items_total', 10, false, '0.00'],
        ['exchanges', 'payment_amount', 10, true, null],
        ['exchanges', 'return_total', 10, false, null],

        // expenses
        ['expenses', 'amount', 15, false, null],

        // financial
        ['financial_journals', 'total_amount', 15, false, '0.00'],
        ['financial_transactions', 'amount', 12, false, null],
        ['financial_transactions', 'balance_after', 12, false, null],
        ['financial_transactions', 'balance_before', 12, false, null],
        ['general_invoices', 'amount', 15, true, null],
        ['journals', 'amount', 15, false, null],
        ['ledger_entries', 'credit', 15, false, '0.00'],
        ['ledger_entries', 'debit', 15, false, '0.00'],

        // gift boxes / cards
        ['gift_boxes', 'price', 10, false, null],
        ['gift_boxes', 'price_btob', 10, true, null],
        ['gift_card_codes', 'original_amount', 10, false, null],
        ['gift_card_codes', 'remaining_amount', 10, false, null],
        ['gift_cards', 'amount', 10, false, null],

        // orders (website)
        ['order_items', 'subtotal', 10, false, null],
        ['order_items', 'unit_price', 10, false, null],
        ['orders', 'deposit_amount', 10, false, '0.00'],
        ['orders', 'discount', 10, false, '0.00'],
        ['orders', 'shipping_cost', 10, false, '0.00'],
        ['orders', 'subtotal', 10, false, null],
        ['orders', 'tax', 10, false, '0.00'],
        ['orders', 'total', 10, false, null],
        ['payment_transactions', 'amount', 10, false, null],
        ['payment_transactions', 'refund_amount', 10, true, null],

        // products / suppliers
        ['product_supplier', 'purchase_price', 10, true, null],
        ['products', 'price', 10, false, '0.00'],
        ['products', 'price_btob', 10, true, null],

        // raw materials (monétaires uniquement)
        ['raw_material_stock_batches', 'unit_price', 12, false, '0.00'],
        ['raw_material_supplier', 'purchase_price', 10, true, null],

        // refills
        ['refill_product', 'purchase_price', 10, false, null],

        // resellers
        ['reseller_product_prices', 'price', 10, false, null],
        ['reseller_sales_report_items', 'unit_price', 10, false, null],
        ['reseller_stock_batches', 'unit_price', 10, false, null],
        ['reseller_stock_deliveries', 'shipping_cost', 10, true, null],
        ['reseller_stock_delivery_product', 'unit_price', 10, false, null],
        ['resellers_invoice_payments', 'amount', 10, false, null],
        ['resellers_invoices', 'total_amount', 10, false, null],

        // staff / salaires
        ['salary_adjustments', 'amount', 10, false, null],
        ['salary_adjustments', 'hourly_rate', 8, true, null],
        ['salary_advances', 'amount', 12, false, null],
        ['salary_payments', 'absence_deduction', 12, false, '0.00'],
        ['salary_payments', 'advances_deduction', 12, false, '0.00'],
        ['salary_payments', 'base_salary', 12, false, null],
        ['salary_payments', 'bonus_amount', 10, false, '0.00'],
        ['salary_payments', 'commission_amount', 10, false, '0.00'],
        ['salary_payments', 'daily_rate', 10, false, null],
        ['salary_payments', 'gross_salary', 10, true, null],
        ['salary_payments', 'net_amount', 12, false, null],
        ['salary_payments', 'other_adjustment_amount', 10, false, '0.00'],
        ['salary_payments', 'overtime_amount', 10, false, '0.00'],
        ['salary_payments', 'penalty_amount', 10, false, '0.00'],
        ['user_salaries', 'base_salary', 12, false, null],

        // sales POS
        ['sale_items', 'price', 10, false, null],
        ['sale_report_items', 'selling_price', 12, false, '0.00'],
        ['sale_report_items', 'total', 12, false, null],
        ['sale_report_items', 'unit_price', 12, false, null],
        ['sale_reports', 'total_amount_invoiced', 12, false, '0.00'],
        ['sale_reports', 'total_amount_theoretical', 12, false, '0.00'],
        ['sales', 'total', 10, false, null],

        // shifts
        ['shifts', 'cash_difference', 10, true, null],
        ['shifts', 'cash_in', 10, false, '0.00'],
        ['shifts', 'cash_out', 10, false, '0.00'],
        ['shifts', 'closing_cash', 10, true, null],
        ['shifts', 'opening_cash', 10, true, null],

        // shipping (prix uniquement — poids exclus)
        ['shipping_rates', 'price', 10, false, null],

        // stock
        ['stock_batches', 'unit_price', 12, false, '0.00'],
        ['stock_loss_items', 'unit_cost', 10, true, null],
        ['stock_losses', 'refund_amount', 10, true, null],
        ['stock_lots', 'purchase_price', 10, false, null],
        ['stock_movement_items', 'unit_price', 10, true, null],
        ['stock_movements', 'total_amount', 15, true, null],

        // supplier orders
        ['supplier_order_invoice_lines', 'invoiced_price', 10, false, null],
        ['supplier_order_invoice_lines', 'reference_price', 10, false, null],
        ['supplier_order_product', 'invoice_price', 10, true, null],
        ['supplier_order_product', 'purchase_price', 10, false, null],
        ['supplier_order_product', 'sale_price', 10, false, null],
        ['supplier_order_raw_material', 'invoice_price', 10, true, null],
        ['supplier_order_raw_material', 'purchase_price', 10, false, '0.00'],
        ['supplier_orders', 'deposit', 10, false, '0.00'],
        ['supplier_payments', 'amount', 15, false, null],
        ['supplier_return_items', 'unit_price', 10, true, null],

        // vouchers
        ['vouchers', 'amount', 10, false, null],

        // warehouse invoices
        ['warehouse_invoices', 'amount_riel', 12, true, null],
        ['warehouse_invoices', 'amount_usd', 12, true, null],
    ];

    public function up(): void
    {
        foreach ($this->columns as [$table, $column, $precision, $nullable, $default]) {
            $newPrecision = $precision + 3;
            DB::statement($this->buildAlterSql($table, $column, $newPrecision, 5, $nullable, $default));
        }
    }

    public function down(): void
    {
        foreach ($this->columns as [$table, $column, $precision, $nullable, $default]) {
            // Retour à la précision d'origine (P, 2)
            DB::statement($this->buildAlterSql($table, $column, $precision, 2, $nullable, $default));
        }
    }

    private function buildAlterSql(string $table, string $column, int $precision, int $scale, bool $nullable, ?string $default): string
    {
        $sql = "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` DECIMAL({$precision}, {$scale})";
        $sql .= $nullable ? ' NULL' : ' NOT NULL';

        if ($default !== null) {
            $sql .= " DEFAULT '{$default}'";
        } elseif ($nullable) {
            $sql .= ' DEFAULT NULL';
        }

        return $sql;
    }
};
