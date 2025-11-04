<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeContent extends Model
{
    protected $table = 'home_content';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Get content by key
     */
    public static function getByKey(string $key, string $locale = 'fr')
    {
        $content = static::where('key', $key)->first();

        if (!$content || !isset($content->value[$locale])) {
            return '';
        }

        return $content->value[$locale];
    }

    /**
     * Update content by key
     */
    public static function updateByKey(string $key, array $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
