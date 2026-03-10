<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class GenerateProductSeo extends Command
{
    protected $signature = 'products:generate-seo
                            {--dry-run : Preview without saving}
                            {--force : Overwrite existing SEO data}
                            {--only-empty : Only fill products with no SEO data (default behavior)}';

    protected $description = 'Generate SEO title and meta description for all products based on name, description and brand';

    private const STORE_NAME = 'Kabas Concept Store';
    private const TITLE_MAX = 70;
    private const DESC_MAX = 160;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $locales = config('app.website_locales', ['en', 'fr']);

        $products = Product::with('brand')->get();
        $this->info("Processing {$products->count()} products for locales: " . implode(', ', $locales));

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $updated = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $seoTitle = $product->seo_title ?? [];
            $metaDesc = $product->meta_description ?? [];
            $changed = false;

            foreach ($locales as $locale) {
                $hasTitle = !empty($seoTitle[$locale] ?? '');
                $hasDesc = !empty($metaDesc[$locale] ?? '');

                if ($hasTitle && $hasDesc && !$force) {
                    continue;
                }

                $name = $product->name[$locale] ?? '';
                $brand = $product->brand->name ?? '';
                $rawDesc = strip_tags($product->description[$locale] ?? '');

                // Fallback to other locale if name is empty
                if (empty($name)) {
                    foreach ($locales as $fallbackLocale) {
                        if (!empty($product->name[$fallbackLocale] ?? '')) {
                            $name = $product->name[$fallbackLocale];
                            break;
                        }
                    }
                }

                if (empty($name)) {
                    continue; // No name at all, skip
                }

                // Generate SEO title
                if (!$hasTitle || $force) {
                    $seoTitle[$locale] = $this->generateTitle($name, $brand);
                    $changed = true;
                }

                // Generate meta description
                if (!$hasDesc || $force) {
                    $metaDesc[$locale] = $this->generateMetaDescription($rawDesc, $name, $brand, $locale);
                    $changed = true;
                }
            }

            if (!$changed) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line('');
                $this->info("Product #{$product->id}: " . ($product->name['en'] ?? $product->name['fr'] ?? $product->ean));
                foreach ($locales as $locale) {
                    $this->line("  [{$locale}] Title: " . ($seoTitle[$locale] ?? '—'));
                    $this->line("  [{$locale}] Desc:  " . ($metaDesc[$locale] ?? '—'));
                }
            } else {
                $product->update([
                    'seo_title' => $seoTitle,
                    'meta_description' => $metaDesc,
                ]);
            }

            $updated++;
        }

        $this->info('');
        $this->info("Done. Updated: {$updated}, Skipped: {$skipped}");

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to save changes.');
        }

        return self::SUCCESS;
    }

    /**
     * Generate SEO title: "Product Name | Brand - Kabas Concept Store"
     * Truncated intelligently to fit within TITLE_MAX chars.
     */
    private function generateTitle(string $name, string $brand): string
    {
        $name = $this->cleanText($name);

        // Try full format: "Name | Brand - Store"
        if (!empty($brand)) {
            $full = "{$name} | {$brand} - " . self::STORE_NAME;
            if (mb_strlen($full) <= self::TITLE_MAX) {
                return $full;
            }

            // Try: "Name | Brand"
            $medium = "{$name} | {$brand}";
            if (mb_strlen($medium) <= self::TITLE_MAX) {
                return $medium;
            }
        }

        // Try: "Name - Store"
        $withStore = "{$name} - " . self::STORE_NAME;
        if (mb_strlen($withStore) <= self::TITLE_MAX) {
            return $withStore;
        }

        // Just the name, truncated
        return mb_substr($name, 0, self::TITLE_MAX);
    }

    /**
     * Generate meta description from the product description text.
     * Falls back to a generated sentence from name + brand.
     */
    private function generateMetaDescription(string $rawDesc, string $name, string $brand, string $locale): string
    {
        $rawDesc = $this->cleanText($rawDesc);

        if (!empty($rawDesc)) {
            return $this->smartTruncate($rawDesc, self::DESC_MAX);
        }

        // No description: generate a fallback
        $name = $this->cleanText($name);
        $brandPart = !empty($brand) ? " {$brand}" : '';

        if ($locale === 'fr') {
            $fallback = "Découvrez {$name}{$brandPart} sur Kabas Concept Store. Livraison au Cambodge et à l'international.";
        } else {
            $fallback = "Discover {$name}{$brandPart} at Kabas Concept Store. Delivery in Cambodia and worldwide.";
        }

        return mb_substr($fallback, 0, self::DESC_MAX);
    }

    /**
     * Truncate text at the last sentence boundary or word boundary within max length.
     */
    private function smartTruncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $max);

        // Try to cut at last sentence end (. ! ?)
        $lastSentence = max(
            mb_strrpos($truncated, '. ') ?: 0,
            mb_strrpos($truncated, '! ') ?: 0,
            mb_strrpos($truncated, '? ') ?: 0,
            mb_strrpos($truncated, ".\n") ?: 0,
        );

        if ($lastSentence > $max * 0.4) {
            return mb_substr($truncated, 0, $lastSentence + 1);
        }

        // Cut at last word boundary
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace > $max * 0.6) {
            return mb_substr($truncated, 0, $lastSpace) . '...';
        }

        return $truncated . '...';
    }

    /**
     * Clean text: normalize whitespace, trim.
     */
    private function cleanText(string $text): string
    {
        // Replace multiple whitespace/newlines with single space
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
