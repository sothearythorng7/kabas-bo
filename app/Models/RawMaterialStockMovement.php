<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'raw_material_stock_batch_id',
        'quantity',
        'type',
        'reference',
        'source_type',
        'source_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    /**
     * Types de mouvements
     */
    const TYPE_PURCHASE = 'purchase';
    const TYPE_PRODUCTION = 'production';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_LOSS = 'loss';

    /**
     * Matière première associée
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    /**
     * Lot de stock associé (si applicable)
     */
    public function stockBatch()
    {
        return $this->belongsTo(RawMaterialStockBatch::class, 'raw_material_stock_batch_id');
    }

    /**
     * Source polymorphique (Production, Adjustment, etc.)
     */
    public function source()
    {
        return $this->morphTo();
    }

    /**
     * Utilisateur qui a effectué le mouvement
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si c'est une entrée de stock
     */
    public function isIncoming(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Vérifie si c'est une sortie de stock
     */
    public function isOutgoing(): bool
    {
        return $this->quantity < 0;
    }

    /**
     * Scope par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
