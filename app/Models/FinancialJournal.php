<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialJournal extends Model
{
    use HasFactory;

    protected $table = 'journals'; // nom exact de la table

    protected $fillable = [
        'store_id',
        'type',      
        'account_id',
        'amount',
        'reference',
        'description',
        'document_path',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
