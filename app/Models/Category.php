<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['parent_id'];

    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products() { return $this->belongsToMany(Product::class)->withTimestamps(); }


    public function translations(): HasMany {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function translation($locale = null) {
        $locale = $locale ?? app()->getLocale();
        return $this->translations()->where('locale', $locale)->first();
    }

    public function fullPathName($locale = null)
    {
        $name = $this->translation($locale)?->name ?? '—';

        if ($this->parent) {
            return $this->parent->fullPathName($locale) . ' > ' . $name;
        }

        return $name;
    }

    
}
