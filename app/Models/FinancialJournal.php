<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'date',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
