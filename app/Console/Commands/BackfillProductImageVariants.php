<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Services\Images\ImageVariantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackfillProductImageVariants extends Command
{
    protected $signature = 'products:backfill-image-variants
        {--product= : Only process images of this product ID}
        {--force : Regenerate variants even if files already exist}';

    protected $description = 'Generate thumb/medium/large variants (WebP+JPEG) for existing product images';

    public function handle(ImageVariantService $service): int
    {
        if ($service->shouldSkip()) {
            $this->error('Variant generation is disabled. Set IMAGE_VARIANTS_ENABLED=true in .env first.');
            return self::FAILURE;
        }

        $query = ProductImage::query()->orderBy('id');
        if ($productId = $this->option('product')) {
            $query->where('product_id', $productId);
            $this->info("Scoped to product ID {$productId}.");
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No product images to process.');
            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $this->info("Processing {$total} product image(s)" . ($force ? ' (force regenerate)' : '') . '...');

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $generated = 0;
        $skipped = 0;
        $errors = 0;

        $query->chunkById(50, function ($images) use ($service, $force, $bar, &$generated, &$skipped, &$errors) {
            foreach ($images as $image) {
                try {
                    if (!$force && $this->allVariantsExist($image)) {
                        $skipped++;
                    } else {
                        $service->generate($image);
                        $generated++;
                    }
                } catch (Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->warn("Image #{$image->id} ({$image->path}): {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Generated: {$generated}  ·  Skipped (already present): {$skipped}  ·  Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function allVariantsExist(ProductImage $image): bool
    {
        $path = ltrim($image->path ?? '', '/');
        if ($path === '') {
            return false;
        }

        $dir = dirname($path);
        $base = pathinfo($path, PATHINFO_FILENAME);
        $disk = Storage::disk('public');

        foreach (array_keys(config('images.variants', [])) as $size) {
            foreach ((array) config('images.formats', []) as $format) {
                $variantPath = ($dir === '.' ? '' : $dir . '/') . $base . "-{$size}.{$format}";
                if (!$disk->exists($variantPath)) {
                    return false;
                }
            }
        }
        return true;
    }
}
