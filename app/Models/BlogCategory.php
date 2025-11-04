<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
        'description' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relations
    public function posts()
    {
        return $this->hasMany(BlogPost::class);
    }

    // Helpers
    public function setTranslation(string $field, string $locale, ?string $value): void
    {
        $translations = $this->{$field} ?? [];
        $translations[$locale] = $value;
        $this->{$field} = $translations;
    }

    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->{$field}[$locale] ?? null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
