<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategorySlugHistory extends Model
{
    use HasFactory;

    protected $table = 'category_slug_histories';

    protected $fillable = [
        'category_id',
        'locale',
        'old_full_slug',
        'new_full_slug',
    ];

    /**
     * La catégorie associée
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
