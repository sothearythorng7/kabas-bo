<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'raw_material_id',
        'quantity_consumed',
    ];

    protected $casts = [
        'quantity_consumed' => 'decimal:4',
    ];

    /**
     * Production associée
     */
    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    /**
     * Matière première consommée
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
