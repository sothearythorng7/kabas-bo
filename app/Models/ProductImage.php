<?php

namespace App\Models;

use App\Services\Images\ImageVariantService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = ['product_id','path','is_primary','sort_order'];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function product() { return $this->belongsTo(Product::class); }

    /**
     * Public URL for a given variant size/format. Falls back to the original
     * upload when the variant file is missing on disk (e.g. job not yet run,
     * dev environment with variant generation skipped).
     */
    public function urlFor(string $size = 'original', string $format = 'webp'): string
    {
        $original = asset('storage/' . ltrim($this->path ?? '', '/'));

        if ($size === 'original' || empty($this->path)) {
            return $original;
        }

        return app(ImageVariantService::class)->urlFor($this, $size, $format) ?? $original;
    }

    /**
     * Convenience array of all variant URLs for the configured formats.
     */
    public function srcset(string $format = 'webp'): array
    {
        $out = [];
        foreach (array_keys(config('images.variants', [])) as $size) {
            $out[$size] = $this->urlFor($size, $format);
        }
        return $out;
    }
}
