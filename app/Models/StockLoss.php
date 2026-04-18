<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLoss extends Model
{
    protected $fillable = [
        'store_id', 'created_by_user_id', 'type', 'supplier_id',
        'status', 'reference', 'reason', 'notes',
        'validated_at', 'refund_requested_at', 'refund_received_at', 'refund_amount',
        'financial_transaction_id', 'refund_transaction_id',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'refund_requested_at' => 'datetime',
        'refund_received_at' => 'datetime',
        'refund_amount' => 'decimal:5',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(StockLossItem::class);
    }

    public function financialTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function refundTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class, 'refund_transaction_id');
    }

    public function getTotalQuantityAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getTotalValueAttribute()
    {
        return $this->items->sum(fn($item) => $item->quantity * ($item->unit_cost ?? 0));
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isValidated()
    {
        return in_array($this->status, ['validated', 'refund_requested', 'refund_received']);
    }

    public function isRefundRequested()
    {
        return $this->status === 'refund_requested';
    }

    public function isRefundReceived()
    {
        return $this->status === 'refund_received';
    }

    public function isEditable()
    {
        return $this->status === 'draft';
    }

    public function isPureLoss()
    {
        return $this->type === 'pure_loss';
    }

    public function isSupplierRefund()
    {
        return $this->type === 'supplier_refund';
    }

    public static function generateReference(): string
    {
        $year = date('Y');
        $lastLoss = static::where('reference', 'like', "LOSS-{$year}-%")
            ->orderByRaw("CAST(SUBSTRING_INDEX(reference, '-', -1) AS UNSIGNED) DESC")
            ->first();

        $nextNumber = 1;
        if ($lastLoss) {
            $parts = explode('-', $lastLoss->reference);
            $nextNumber = (int) end($parts) + 1;
        }

        return sprintf('LOSS-%s-%04d', $year, $nextNumber);
    }
}
