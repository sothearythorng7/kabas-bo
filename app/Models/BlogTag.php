<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
    ];

    // Relations
    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
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
}
