<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'unit',
        'track_stock',
        'alert_quantity',
        'factory_supplier_id', // Deprecated, utilisez supplier_id
        'supplier_id',
        'is_active',
    ];

    protected $casts = [
        'track_stock' => 'boolean',
        'is_active' => 'boolean',
        'alert_quantity' => 'decimal:2',
    ];

    /**
     * Fournisseur de cette matière première
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Ancien fournisseur (deprecated)
     */
    public function factorySupplier()
    {
        return $this->belongsTo(FactorySupplier::class, 'factory_supplier_id');
    }

    /**
     * Lots de stock pour cette matière première
     */
    public function stockBatches()
    {
        return $this->hasMany(RawMaterialStockBatch::class);
    }

    /**
     * Mouvements de stock pour cette matière première
     */
    public function stockMovements()
    {
        return $this->hasMany(RawMaterialStockMovement::class);
    }

    /**
     * Recettes qui utilisent cette matière première
     */
    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }

    /**
     * Recettes (via recipe_items)
     */
    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_items')
            ->withPivot('quantity', 'is_optional', 'notes')
            ->withTimestamps();
    }

    /**
     * Consommations en production
     */
    public function productionConsumptions()
    {
        return $this->hasMany(ProductionConsumption::class);
    }

    /**
     * Stock total disponible
     */
    public function getTotalStockAttribute(): float
    {
        if (!$this->track_stock) {
            return 0;
        }
        return $this->stockBatches()->sum('quantity');
    }

    /**
     * Vérifie si le stock est en alerte
     */
    public function isLowStock(): bool
    {
        if (!$this->track_stock || is_null($this->alert_quantity)) {
            return false;
        }
        return $this->total_stock <= $this->alert_quantity;
    }

    /**
     * Scope pour les matières premières actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les matières premières avec gestion de stock
     */
    public function scopeTracked($query)
    {
        return $query->where('track_stock', true);
    }

    /**
     * Retrait de stock (FIFO)
     */
    public function removeStock(float $quantity, ?Production $production = null, ?User $user = null): bool
    {
        if (!$this->track_stock) {
            return true; // Pas de gestion de stock, on considère toujours OK
        }

        $batches = $this->stockBatches()
            ->where('quantity', '>', 0)
            ->orderBy('received_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $toDeduct = min($batch->quantity, $remaining);
            $batch->quantity -= $toDeduct;
            $batch->save();

            $remaining -= $toDeduct;
        }

        // Enregistrer le mouvement de stock
        $this->stockMovements()->create([
            'quantity' => -$quantity,
            'type' => 'production',
            'source_type' => $production ? Production::class : null,
            'source_id' => $production?->id,
            'user_id' => $user?->id,
        ]);

        return $remaining <= 0;
    }

    /**
     * Ajout de stock
     */
    public function addStock(float $quantity, float $unitPrice = 0, ?array $data = [], ?User $user = null): RawMaterialStockBatch
    {
        $batch = $this->stockBatches()->create([
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'received_at' => $data['received_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'batch_number' => $data['batch_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Enregistrer le mouvement de stock
        if ($this->track_stock) {
            $this->stockMovements()->create([
                'raw_material_stock_batch_id' => $batch->id,
                'quantity' => $quantity,
                'type' => 'purchase',
                'source_type' => RawMaterialStockBatch::class,
                'source_id' => $batch->id,
                'user_id' => $user?->id,
            ]);
        }

        return $batch;
    }
}
