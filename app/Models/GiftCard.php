<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function codes()
    {
        return $this->hasMany(GiftCardCode::class);
    }

    // URL publique de la gift card
    public function publicUrl($locale = 'fr')
    {
        $slug = Str::slug($this->name[$locale] ?? $this->name['fr'] ?? 'gift-card');
        return url("/{$locale}/gift-card/{$slug}-{$this->id}");
    }

    // Attribut pour le nom dans la locale actuelle
    public function getDisplayNameAttribute()
    {
        $locale = app()->getLocale();
        return $this->name[$locale] ?? $this->name['fr'] ?? 'Gift Card';
    }
}
