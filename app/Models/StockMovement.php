<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',           // 'transfer', 'in', 'out', 'adjustment'
        'from_store_id',
        'to_store_id',
        'note',
        'user_id',
        'status',
        'total_amount',
        'invoice_number',
        'invoice_path',
        'from_transaction_id',
        'to_transaction_id',
    ];

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_VALIDATED  = 'validated';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED   = 'received';
    public const STATUS_CANCELLED  = 'cancelled';

    public const TYPE_TRANSFER   = 'transfer';
    public const TYPE_IN         = 'in';
    public const TYPE_OUT        = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public function items()
    {
        return $this->hasMany(StockMovementItem::class);
    }

    public function fromStore()
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class, 'from_transaction_id');
    }

    public function toTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class, 'to_transaction_id');
    }

    /**
     * Génère un numéro de facture unique pour les transferts
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'TRF';
        $year = date('Y');
        $month = date('m');

        $lastMovement = self::whereNotNull('invoice_number')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMovement && preg_match('/TRF-\d{6}-(\d+)/', $lastMovement->invoice_number, $matches)) {
            $sequence = intval($matches[1]) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }
}

