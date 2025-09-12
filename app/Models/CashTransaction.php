<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'total_amount',
        'transaction_date',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function items()
    {
        return $this->hasMany(CashTransactionItem::class);
    }
}
