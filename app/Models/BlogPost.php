<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'blog_category_id',
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'is_published',
        'published_at',
        'meta_title',
        'meta_description',
        'views_count',
    ];

    protected $casts = [
        'title' => 'array',
        'slug' => 'array',
        'excerpt' => 'array',
        'content' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
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

    public function publicUrl(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $slug = $this->slug[$locale] ?? '';
        $publicSiteUrl = config('app.site_public_url', url('/'));
        return rtrim($publicSiteUrl, '/') . "/{$locale}/blog/{$slug}";
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }
}
