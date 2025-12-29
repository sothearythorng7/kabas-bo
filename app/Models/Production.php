<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'quantity_produced',
        'produced_at',
        'batch_number',
        'status',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'produced_at' => 'date',
        'quantity_produced' => 'integer',
    ];

    /**
     * Statuts de production
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Recette utilisée
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Produit fini (via recipe)
     */
    public function product()
    {
        return $this->hasOneThrough(Product::class, Recipe::class, 'id', 'id', 'recipe_id', 'product_id');
    }

    /**
     * Consommations de matières premières
     */
    public function consumptions()
    {
        return $this->hasMany(ProductionConsumption::class);
    }

    /**
     * Matières premières consommées
     */
    public function rawMaterials()
    {
        return $this->belongsToMany(RawMaterial::class, 'production_consumptions')
            ->withPivot('quantity_consumed')
            ->withTimestamps();
    }

    /**
     * Utilisateur qui a enregistré la production
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Stock batches créés par cette production
     */
    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class, 'source_production_id');
    }

    /**
     * Scope par statut
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour les productions terminées
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Vérifie si la production est terminée
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Vérifie si la production peut être modifiée
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Génère un numéro de lot unique
     */
    public static function generateBatchNumber(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now()->toDateString())->count() + 1;
        return "PROD-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
