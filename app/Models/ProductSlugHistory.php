<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSlugHistory extends Model
{
    protected $table = 'product_slug_histories';

    protected $fillable = [
        'product_id',
        'locale',
        'old_slug',
        'new_slug',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
