<?php

namespace App\Models;

// app/Models/Page.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title','content','slugs','meta_title','meta_description',
        'is_published','published_at',
    ];

    protected $casts = [
        'title' => 'array',
        'content' => 'array',
        'slugs' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function setTranslation(string $field, string $locale, ?string $value) {
        $data = $this->{$field} ?? [];
        if (!is_array($data)) $data = [];
        $data[$locale] = $value ?? '';
        $this->{$field} = $data;
        return $this;
    }

    /** Trouver par slug en respectant la locale courante */
    public function scopeWhereSlug($q, string $slug, ?string $locale = null) {
        $loc = $locale ?: app()->getLocale();
        return $q->where("slugs->$loc", $slug);
    }
}
