<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariationAttribute;
use App\Models\VariationGroup;
use App\Models\VariationType;
use App\Models\VariationValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixVariationAttributes extends Command
{
    protected $signature = 'variations:fix-attributes {--dry-run : Show changes without applying}';
    protected $description = 'Fix variation attributes by extracting actual values from product names';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $groups = VariationGroup::with([
            'products.variationAttributes.type',
            'products.variationAttributes.value',
        ])->has('products', '>=', 2)->get();

        $fixed = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            $products = $group->products->sortBy('id');
            $names = $products->mapWithKeys(fn($p) => [
                $p->id => $p->name['fr'] ?? $p->name['en'] ?? reset($p->name) ?? '',
            ]);

            // Find the varying part of each product name
            $labels = $this->extractDiffLabels($names->values()->toArray());

            if (empty($labels) || count(array_unique($labels)) <= 1) {
                // Names are too similar, try EAN
                $labels = $this->extractDiffLabels($products->pluck('ean')->toArray());
            }

            if (empty($labels) || count(array_unique($labels)) <= 1) {
                $this->warn("Group {$group->id} ({$group->name}): cannot extract labels, skipping");
                $skipped++;
                continue;
            }

            // Determine which variation type to use from existing attributes
            $existingAttrs = $products->flatMap->variationAttributes;
            $typeId = $existingAttrs->first()?->variation_type_id;

            if (!$typeId) {
                $this->warn("Group {$group->id}: no type found, skipping");
                $skipped++;
                continue;
            }

            $type = VariationType::find($typeId);
            $productIds = $products->pluck('id')->values();

            if ($dryRun) {
                $this->info("Group {$group->id} ({$group->name}) — type: {$type->name}");
            }

            DB::transaction(function () use ($productIds, $labels, $typeId, $type, $group, $dryRun, &$fixed) {
                foreach ($productIds as $i => $productId) {
                    $label = strtoupper(trim($labels[$i] ?? ''));
                    if (empty($label)) continue;

                    // Find or create the variation value
                    $value = VariationValue::firstOrCreate(
                        ['variation_type_id' => $typeId, 'value' => $label],
                    );

                    if ($dryRun) {
                        $this->line("  Product {$productId}: {$type->name} = {$label}");
                        continue;
                    }

                    // Update or create the attribute
                    ProductVariationAttribute::updateOrCreate(
                        [
                            'product_id' => $productId,
                            'variation_group_id' => $group->id,
                            'variation_type_id' => $typeId,
                        ],
                        [
                            'variation_value_id' => $value->id,
                        ]
                    );
                }
                $fixed++;
            });
        }

        $this->newLine();
        $this->info("Done: {$fixed} groups fixed, {$skipped} skipped.");
        if ($dryRun) {
            $this->warn('Dry run — no changes applied.');
        }

        return self::SUCCESS;
    }

    /**
     * Extract the differing part from an array of similar strings.
     * "Kabas Pepper 100g" / "Kabas Pepper 500g" → ["100g", "500g"]
     * "Espadrille Blue 39" / "Espadrille Blue 38" → ["39", "38"]
     * "Floating Village (A6)" / "Floating Village (A5)" → ["(A6)", "(A5)"] → cleaned to ["A6", "A5"]
     */
    private function extractDiffLabels(array $names): array
    {
        if (count($names) < 2) return $names;

        $wordArrays = array_map(fn($n) => preg_split('/[\s]+/', trim($n)), $names);
        $minWords = min(array_map('count', $wordArrays));
        $first = $wordArrays[0];

        // Find common prefix
        $prefix = 0;
        for ($i = 0; $i < $minWords; $i++) {
            $word = strtolower($first[$i]);
            $allMatch = true;
            foreach ($wordArrays as $words) {
                if (strtolower($words[$i] ?? '') !== $word) {
                    $allMatch = false;
                    break;
                }
            }
            if ($allMatch) $prefix++;
            else break;
        }

        // Find common suffix
        $suffix = 0;
        for ($i = 1; $i <= $minWords - $prefix; $i++) {
            $word = strtolower($first[count($first) - $i]);
            $allMatch = true;
            foreach ($wordArrays as $words) {
                if (strtolower($words[count($words) - $i] ?? '') !== $word) {
                    $allMatch = false;
                    break;
                }
            }
            if ($allMatch) $suffix++;
            else break;
        }

        $labels = [];
        foreach ($wordArrays as $words) {
            $mid = array_slice($words, $prefix, count($words) - $prefix - $suffix);
            $label = implode(' ', $mid);
            // Clean parentheses: "(A6)" → "A6"
            $label = preg_replace('/^\((.+)\)$/', '$1', $label);
            $labels[] = $label;
        }

        return $labels;
    }
}
