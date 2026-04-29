<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSlugHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplySeoFixes extends Command
{
    protected $signature = 'products:apply-seo-fixes
        {--file=database/seo-fixes/2026-04-29.json : JSON file with fixes}
        {--apply : Actually write changes (default is dry-run)}
        {--only= : Comma-separated fix types to apply (e.g. TRAD,TYPO,SLUG+301)}
        {--slug= : Apply only to a single product (matches against any locale slug)}';

    protected $description = 'Apply SEO product fixes from JSON spec (translations, slugs+301, categories).';

    /**
     * Mapping from human-readable category path (as used in the SEO markdown)
     * to the actual category id in the DB. Verified against category_translations
     * before this command was written.
     */
    private const CATEGORY_MAP = [
        // ADD_CAT / MOVE targets
        'Gifts & Vouchers > Gift Ideas'               => 119,
        'Gifts & Vouchers > Gift Sets'                => 123, // DB: gifts-sets
        'Gifts & Vouchers > Souvenir Gifts'           => 120,
        'Home & Lifestyle > Home & Déco'              => 24,
        'Home & Lifestyle > Everyday Items'           => 31,
        'Health & Beauty > Aromatherapy & Fragrance'  => 103,
        'Health & Beauty > Body Care'                 => 97,
        'Clothing & Accessories > Accessories'        => 69,
        'Jewelry & Handicraft > Handicraft'           => 87, // mapped to Handicraft Creations
        // MOVE source synonyms used in the markdown
        'Home & Lifestyle > Collections > Gift Sets'  => 40,
        'Wallets & Small Items'                       => 72,
        'Accessories (catégorie principale)'          => 69,
    ];

    /**
     * Types that are auto-applicable as a simple field update (locale inferred).
     */
    private const NAME_TYPES = [
        'TYPO', 'GRAMMAR', 'FORMAT', 'FORMAT+SEO',
        'SEO_RENAME', 'URGENT_RENAME', 'TRAD',
        'TYPO+TRAD', 'TYPO+FORMAT', '404_FIX',
    ];

    private const DESC_DIRECTIVE_TYPES = ['SEO_ENRICH', 'REWRITE'];

    private const MANUAL_TYPES = ['VERIFY', 'REVIEW', 'TRAD+REVIEW', 'URGENT'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $onlyOpt = $this->option('only');
        $only = $onlyOpt ? array_map('trim', explode(',', $onlyOpt)) : null;
        $slugFilter = $this->option('slug');

        $path = base_path($this->option('file'));
        if (!is_file($path)) {
            $this->error("File not found: $path");
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            $this->error("Invalid JSON: $path");
            return self::FAILURE;
        }

        $stats = ['applied' => 0, 'manual' => 0, 'directive' => 0, 'not_found' => 0, 'error' => 0, 'skipped_filter' => 0];
        $manualReport = [];
        $directiveReport = [];

        foreach ($data as $entry) {
            $sourceSlug = $entry['slug'] ?? null;
            if (!$sourceSlug) continue;
            if ($slugFilter && $sourceSlug !== $slugFilter) continue;

            $product = $this->findProduct($sourceSlug);
            if (!$product) {
                $stats['not_found']++;
                $this->error("  ✗ Product not found by slug: $sourceSlug");
                continue;
            }

            $this->line('');
            $this->info("[{$product->id}] $sourceSlug");

            foreach ($entry['fixes'] as $fix) {
                $type = $fix['type'] ?? '';
                if ($only && !in_array($type, $only, true)) {
                    $stats['skipped_filter']++;
                    continue;
                }

                try {
                    $result = $this->applyFix($product, $fix, $apply);
                } catch (\Throwable $e) {
                    $stats['error']++;
                    $this->error("    ✗ {$fix['field']} ($type): {$e->getMessage()}");
                    continue;
                }

                switch ($result['status']) {
                    case 'applied':
                        $stats['applied']++;
                        $marker = $apply ? '✓' : '◇';
                        $this->line("    $marker {$fix['field']} ($type) — {$result['detail']}");
                        break;
                    case 'manual':
                        $stats['manual']++;
                        $manualReport[] = "[$sourceSlug] {$fix['field']} ($type) — {$result['detail']}";
                        $this->comment("    ⚠ {$fix['field']} ($type) — manual: {$result['detail']}");
                        break;
                    case 'directive':
                        $stats['directive']++;
                        $directiveReport[] = "[$sourceSlug] {$fix['field']} ($type) — {$result['detail']}";
                        $this->comment("    ⏭ {$fix['field']} ($type) — directive (Phase 4): {$result['detail']}");
                        break;
                    case 'noop':
                        $this->line("    · {$fix['field']} ($type) — {$result['detail']}");
                        break;
                }
            }
        }

        $this->line('');
        $this->line(str_repeat('─', 60));
        $this->info("Stats:");
        foreach ($stats as $k => $v) {
            $this->line("  $k: $v");
        }

        if (!empty($manualReport)) {
            $this->line('');
            $this->warn('Manual review needed:');
            foreach ($manualReport as $r) $this->line("  $r");
        }

        if (!empty($directiveReport)) {
            $this->line('');
            $this->warn('Directives for Phase 4 (descriptions to write):');
            foreach ($directiveReport as $r) $this->line("  $r");
        }

        if (!$apply) {
            $this->line('');
            $this->comment('DRY-RUN. Re-run with --apply to commit.');
        }

        return self::SUCCESS;
    }

    private function findProduct(string $slug): ?Product
    {
        // Try current EN slug, then FR slug
        $p = Product::where('slugs->en', $slug)->first()
            ?? Product::where('slugs->fr', $slug)->first();
        if ($p) return $p;

        // Fallback: lookup in product_slug_histories (the slug may have been changed in
        // a previous run of this command and the JSON spec still references the old slug).
        $history = ProductSlugHistory::where('old_slug', $slug)->orderByDesc('id')->first();
        if ($history) {
            return Product::find($history->product_id);
        }

        return null;
    }

    /**
     * @return array{status: string, detail: string}
     */
    private function applyFix(Product $product, array $fix, bool $apply): array
    {
        $field = $fix['field'] ?? '';
        $type  = $fix['type']  ?? '';
        $to    = $fix['to']    ?? null;
        $from  = $fix['from']  ?? null;

        // --- Manual review only ---
        if (in_array($type, self::MANUAL_TYPES, true)) {
            return ['status' => 'manual', 'detail' => "to=" . $this->fmt($to)];
        }

        // --- Directives for Phase 4 ---
        if (in_array($type, self::DESC_DIRECTIVE_TYPES, true)) {
            return ['status' => 'directive', 'detail' => "to=" . $this->fmt($to)];
        }

        // --- Field: name_en / name_fr / desc_en / desc_fr (handled as translations) ---
        if (preg_match('/^(name|desc(?:ription)?)_(en|fr)$/', $field, $m)) {
            if (!in_array($type, self::NAME_TYPES, true)) {
                return ['status' => 'manual', 'detail' => "unknown name/desc type: $type"];
            }
            $jsonField = $m[1] === 'name' ? 'name' : 'description';
            $locale = $m[2];
            $current = $product->{$jsonField}[$locale] ?? null;
            if ($current === $to) return ['status' => 'noop', 'detail' => 'already up to date'];

            // Idempotency: if the fix value is already a substring of current, the fix has
            // already been applied (e.g. re-running a TYPO Buterfly→Butterfly on a value that
            // already contains "Butterfly"). Skip to avoid corrupting the field.
            if (is_string($current) && $to !== '' && mb_strpos($current, (string) $to) !== false) {
                return ['status' => 'noop', 'detail' => 'already applied (current contains the fix)'];
            }

            // Decide between substring replace (word-level fix) vs full replacement.
            // Substring iff `from` is a strict substring of current AND `to` is shorter
            // (meaning the fix targets a fragment, not the whole field).
            $current = (string) ($current ?? '');
            $fromIsSubstring = $from !== null && $from !== ''
                && mb_strpos($current, $from) !== false
                && mb_strlen($to) < mb_strlen($current);

            $newValue = $fromIsSubstring
                ? str_replace($from, (string) $to, $current)
                : (string) $to;

            if ($newValue === $current) return ['status' => 'noop', 'detail' => 'already up to date'];

            if ($apply) {
                $product->setTranslation($jsonField, $locale, $newValue);
                $product->save();
            }
            $mode = $fromIsSubstring ? 'substring' : 'full';
            return ['status' => 'applied', 'detail' => "[$mode] " . $this->fmt($current) . ' → ' . $this->fmt($newValue)];
        }

        // --- Field: page_fr (404_FIX) ---
        // The markdown directive says "recreate FR page". In practice the FR translation
        // is already there but slug.fr diverges from slug.en, breaking /fr/product/{en-slug}.
        // Fix: align slug.fr to slug.en. The history hook will record the old fr slug
        // so that the old FR url still 301s.
        if ($field === 'page_fr' && $type === '404_FIX') {
            $slugs = is_array($product->slugs) ? $product->slugs : [];
            $en = $slugs['en'] ?? null;
            $fr = $slugs['fr'] ?? null;
            if (!$en) {
                return ['status' => 'manual', 'detail' => 'no slug.en to align fr against'];
            }
            if ($fr === $en) {
                return ['status' => 'noop', 'detail' => 'slug.fr already matches slug.en'];
            }
            $slugs['fr'] = $en;
            if ($apply) {
                $product->slugs = $slugs;
                $product->save();
            }
            return ['status' => 'applied', 'detail' => "slug.fr '$fr' → '$en' (align with slug.en)"];
        }

        // --- Field: slug (SLUG+301) ---
        if ($field === 'slug' && $type === 'SLUG+301') {
            $slugs = is_array($product->slugs) ? $product->slugs : [];
            $changedLocales = [];
            foreach ($slugs as $loc => $cur) {
                if ($cur === $from) {
                    $slugs[$loc] = $to;
                    $changedLocales[] = $loc;
                }
            }
            if (empty($changedLocales)) {
                return ['status' => 'noop', 'detail' => "no locale matches from='$from'"];
            }
            if ($apply) {
                $product->slugs = $slugs;
                $product->save(); // hook auto-inserts history rows
            }
            return ['status' => 'applied', 'detail' => "slug $from → $to (locales: " . implode(',', $changedLocales) . ')'];
        }

        // --- Field: category (ADD_CAT / MOVE) ---
        if ($field === 'category') {
            $targetId = self::CATEGORY_MAP[$to] ?? null;
            if (!$targetId) {
                return ['status' => 'manual', 'detail' => "unmapped category target: $to"];
            }

            if ($type === 'ADD_CAT') {
                $hasIt = $product->categories()->where('categories.id', $targetId)->exists();
                if ($hasIt) return ['status' => 'noop', 'detail' => "already in category id=$targetId"];
                if ($apply) $product->categories()->syncWithoutDetaching([$targetId]);
                return ['status' => 'applied', 'detail' => "+ category id=$targetId ($to)"];
            }

            if ($type === 'MOVE') {
                $oldId = self::CATEGORY_MAP[$from] ?? null;
                if (!$oldId) {
                    return ['status' => 'manual', 'detail' => "unmapped category source: $from"];
                }
                $detachNeeded = $product->categories()->where('categories.id', $oldId)->exists();
                $attachNeeded = !$product->categories()->where('categories.id', $targetId)->exists();
                if (!$detachNeeded && !$attachNeeded) {
                    return ['status' => 'noop', 'detail' => "already moved"];
                }
                if ($apply) {
                    if ($detachNeeded) $product->categories()->detach($oldId);
                    if ($attachNeeded) $product->categories()->syncWithoutDetaching([$targetId]);
                }
                return ['status' => 'applied', 'detail' => "moved $oldId → $targetId"];
            }
        }

        // --- Field: prix (only in VERIFY context, already filtered above) ---
        if ($field === 'prix') {
            return ['status' => 'manual', 'detail' => "price check needed: $to"];
        }

        return ['status' => 'manual', 'detail' => "unhandled fix: field=$field type=$type"];
    }

    private function fmt($v): string
    {
        if ($v === null) return '(null)';
        $s = (string) $v;
        if (mb_strlen($s) > 70) $s = mb_substr($s, 0, 67) . '...';
        return '"' . $s . '"';
    }
}
