<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = ['product_id','path','is_primary','sort_order'];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function product() { return $this->belongsTo(Product::class); }
}
