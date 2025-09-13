<?php

namespace App\Exports;

use App\Models\FinancialTransaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FinancialTransactionsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected int $storeId;
    protected array $filters;

    public function __construct(int $storeId, array $filters = [])
    {
        $this->storeId = $storeId;
        $this->filters = $filters;
    }

    public function query()
    {
        $query = FinancialTransaction::where('store_id', $this->storeId)
            ->with(['account', 'paymentMethod', 'user']);

        // Appliquer les filtres si présents
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['account_ids'])) {
            $query->whereIn('account_id', $this->filters['account_ids']);
        }
        if (!empty($this->filters['amount_min'])) {
            $query->where('amount', '>=', $this->filters['amount_min']);
        }
        if (!empty($this->filters['amount_max'])) {
            $query->where('amount', '<=', $this->filters['amount_max']);
        }
        if (!empty($this->filters['payment_method_ids'])) {
            $query->whereIn('payment_method_id', $this->filters['payment_method_ids']);
        }

        return $query->orderBy('transaction_date', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Libellé',
            'Description',
            'Compte (code - nom)',
            'Montant',
            'Direction',
            'Solde avant',
            'Solde après',
            'Méthode de paiement',
            'Utilisateur',
            'Devise',
            'Statut',
            'Référence externe',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->id,
            $transaction->transaction_date->format('Y-m-d H:i:s'),
            $transaction->label,
            $transaction->description,
            $transaction->account?->code . ' - ' . $transaction->account?->name,
            $transaction->amount,
            $transaction->direction,
            $transaction->balance_before,
            $transaction->balance_after,
            $transaction->paymentMethod?->name,
            $transaction->user?->name,
            $transaction->currency,
            $transaction->status,
            $transaction->external_reference,
        ];
    }
}
