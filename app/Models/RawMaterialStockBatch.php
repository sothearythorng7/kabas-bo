<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialStockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'quantity',
        'unit_price',
        'received_at',
        'expires_at',
        'batch_number',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'received_at' => 'date',
        'expires_at' => 'date',
    ];

    /**
     * Matière première associée
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    /**
     * Mouvements de stock liés à ce lot
     */
    public function stockMovements()
    {
        return $this->hasMany(RawMaterialStockMovement::class);
    }

    /**
     * Vérifie si le lot est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Vérifie si le lot expire bientôt (30 jours par défaut)
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expires_at && $this->expires_at->isBetween(now(), now()->addDays($days));
    }

    /**
     * Scope pour les lots avec stock disponible
     */
    public function scopeAvailable($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope pour les lots non expirés
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}
