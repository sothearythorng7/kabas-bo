<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $fillable = ['image_path','sort_order','is_active','starts_at','ends_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function scopePublished($q) {
        $now = now();
        return $q->where('is_active', true)
                 ->where(function($w) use ($now){ $w->whereNull('starts_at')->orWhere('starts_at','<=',$now); })
                 ->where(function($w) use ($now){ $w->whereNull('ends_at')->orWhere('ends_at','>=',$now); })
                 ->orderBy('sort_order')->orderBy('id');
    }
}
