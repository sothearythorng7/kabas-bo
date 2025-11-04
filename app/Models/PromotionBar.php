<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PromotionBar extends Model
{
    use HasTranslations;

    protected $fillable = [
        'message',
        'is_active',
    ];

    public $translatable = ['message'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
