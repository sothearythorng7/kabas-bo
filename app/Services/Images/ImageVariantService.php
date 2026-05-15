<?php

namespace App\Services\Images;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Throwable;

class ImageVariantService
{
    public function __construct(private ?ImageManager $manager = null)
    {
        $this->manager ??= new ImageManager(new GdDriver());
    }

    public function shouldSkip(): bool
    {
        return !((bool) config('images.enabled', false));
    }

    /**
     * Generate every configured variant × format for the given image.
     * Returns a list of relative paths written (or empty array if skipped).
     */
    public function generate(ProductImage $image, ?array $only = null): array
    {
        if ($this->shouldSkip()) {
            Log::info('ImageVariantService: skipped (disabled by config)', [
                'image_id' => $image->id,
            ]);
            return [];
        }

        $written = [];
        $variants = config('images.variants', []);

        foreach ($variants as $size => $opts) {
            if ($only !== null && !in_array($size, $only, true)) {
                continue;
            }
            $written = array_merge($written, $this->generateOne($image, $size));
        }

        return $written;
    }

    /**
     * Generate a single variant (all formats) for the given image.
     * Returns the list of relative paths actually written.
     */
    public function generateOne(ProductImage $image, string $size): array
    {
        if ($this->shouldSkip()) {
            return [];
        }

        $variants = config('images.variants', []);
        if (!isset($variants[$size])) {
            Log::warning('ImageVariantService: unknown variant requested', ['size' => $size]);
            return [];
        }

        $disk = Storage::disk('public');
        $sourceRelative = ltrim($image->path ?? '', '/');
        if ($sourceRelative === '' || !$disk->exists($sourceRelative)) {
            Log::warning('ImageVariantService: source missing', [
                'image_id' => $image->id,
                'path' => $sourceRelative,
            ]);
            return [];
        }

        $sourceAbsolute = $disk->path($sourceRelative);
        $width = (int) $variants[$size]['width'];
        $written = [];

        try {
            $img = $this->manager->read($sourceAbsolute);

            // scaleDown: ne grandit pas une image plus petite que la cible
            $img->scaleDown(width: $width);

            foreach ((array) config('images.formats', ['webp', 'jpg']) as $format) {
                $targetRelative = $this->variantPath($sourceRelative, $size, $format);
                $encoded = match ($format) {
                    'webp' => $img->encode(new WebpEncoder(quality: (int) config('images.quality.webp', 82))),
                    'jpg', 'jpeg' => $img->encode(new JpegEncoder(quality: (int) config('images.quality.jpg', 85), progressive: true)),
                    default => null,
                };
                if ($encoded === null) {
                    continue;
                }
                $disk->put($targetRelative, (string) $encoded);
                $written[] = $targetRelative;
            }
        } catch (Throwable $e) {
            Log::error('ImageVariantService: generation failed', [
                'image_id' => $image->id,
                'size' => $size,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $written;
    }

    /**
     * Return a public asset URL for a given variant + format, or null if the
     * variant file does not exist on disk. Callers should fallback to the
     * original asset URL when null is returned.
     */
    public function urlFor(ProductImage $image, string $size, string $format = 'webp'): ?string
    {
        $sourceRelative = ltrim($image->path ?? '', '/');
        if ($sourceRelative === '') {
            return null;
        }

        $variantRelative = $this->variantPath($sourceRelative, $size, $format);
        if (!Storage::disk('public')->exists($variantRelative)) {
            return null;
        }

        return asset('storage/' . $variantRelative);
    }

    /**
     * Delete every variant file derived from the given image. Safe to call
     * even if no variants exist.
     */
    public function delete(ProductImage $image): void
    {
        $disk = Storage::disk('public');
        $sourceRelative = ltrim($image->path ?? '', '/');
        if ($sourceRelative === '') {
            return;
        }

        foreach (config('images.variants', []) as $size => $opts) {
            foreach ((array) config('images.formats', []) as $format) {
                $relative = $this->variantPath($sourceRelative, $size, $format);
                if ($disk->exists($relative)) {
                    $disk->delete($relative);
                }
            }
        }
    }

    /**
     * Derive the variant path from the source path.
     * products/abc.jpg + (thumb, webp) → products/abc-thumb.webp
     */
    private function variantPath(string $sourceRelative, string $size, string $format): string
    {
        $dir = dirname($sourceRelative);
        $base = pathinfo($sourceRelative, PATHINFO_FILENAME);
        $suffix = "-{$size}.{$format}";
        return ($dir === '.' ? '' : $dir . '/') . $base . $suffix;
    }
}
