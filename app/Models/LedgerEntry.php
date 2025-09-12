<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_journal_id',
        'account_id',
        'type', // debit or credit
        'amount',
        'description',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function financialJournal()
    {
        return $this->belongsTo(FinancialJournal::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
