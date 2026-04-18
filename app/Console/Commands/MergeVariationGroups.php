<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariationAttribute;
use App\Models\VariationGroup;
use App\Models\VariationType;
use App\Models\VariationValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeVariationGroups extends Command
{
    protected $signature = 'variations:merge-groups {--dry-run : Show changes without applying}';
    protected $description = 'Detect and merge variation groups that differ by a second dimension (e.g. color)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $groups = VariationGroup::with('products.variationAttributes.type')->has('products', '>=', 2)->get();

        // For each group, compute: base (common prefix minus last word) + dimension (last prefix word)
        $groupSignatures = [];
        foreach ($groups as $g) {
            $names = $g->products->map(fn($p) => strtolower($p->name['fr'] ?? $p->name['en'] ?? ''))->toArray();
            $words = array_map(fn($n) => preg_split('/[\s\-]+/', trim($n)), $names);
            $minLen = min(array_map('count', $words));
            if ($minLen < 2) continue;

            $first = $words[0];

            $prefix = 0;
            for ($i = 0; $i < $minLen; $i++) {
                $w = $first[$i];
                $allMatch = true;
                foreach ($words as $wa) {
                    if (($wa[$i] ?? '') !== $w) { $allMatch = false; break; }
                }
                if ($allMatch) $prefix++;
                else break;
            }

            if ($prefix < 2) continue;

            $prefixWords = array_slice($first, 0, $prefix);
            $base = implode(' ', array_slice($prefixWords, 0, -1));
            $dimension = end($prefixWords);

            $groupSignatures[$g->id] = [
                'base' => $base,
                'dimension' => $dimension,
                'prefix_len' => $prefix,
            ];
        }

        // Find groups sharing the same base with different dimensions
        $baseToGroups = [];
        foreach ($groupSignatures as $gId => $sig) {
            $baseToGroups[$sig['base']][] = ['group_id' => $gId, 'dimension' => $sig['dimension']];
        }

        $mergeable = [];
        foreach ($baseToGroups as $base => $items) {
            if (count($items) < 2) continue;
            $dims = array_column($items, 'dimension');
            if (count(array_unique($dims)) <= 1) continue;

            // Validate: the dimension should look like a color, size, variant etc.
            // Skip if products in the groups don't share the same variation type
            $firstGroup = $groups->find($items[0]['group_id']);
            $firstType = $firstGroup->products->first()?->variationAttributes->first()?->type;
            if (!$firstType) continue;

            $allSameType = true;
            foreach ($items as $item) {
                $g = $groups->find($item['group_id']);
                $type = $g->products->first()?->variationAttributes->first()?->type;
                if (!$type || $type->id !== $firstType->id) {
                    $allSameType = false;
                    break;
                }
            }
            if (!$allSameType) continue;

            $mergeable[$base] = $items;
        }

        if (empty($mergeable)) {
            $this->info('No groups to merge.');
            return self::SUCCESS;
        }

        // Determine the variation type for the new dimension (color, size, etc.)
        $colorType = VariationType::where('name', 'color')->first();
        $sizeType = VariationType::where('name', 'size')->first();

        // Known color words
        $colorWords = ['red', 'blue', 'black', 'white', 'green', 'orange', 'purple', 'yellow',
            'pink', 'gray', 'grey', 'cream', 'beig', 'beige', 'brown', 'navy', 'gold', 'silver',
            'amber', 'lavender', 'mint', 'lime', 'beach', 'star', 'sendy'];
        // Known size words
        $sizeWords = ['big', 'small', 'xs', 's', 'm', 'l', 'xl', '2xl', 'xxl'];

        $merged = 0;

        foreach ($mergeable as $base => $items) {
            $dims = array_column($items, 'dimension');

            // Determine what type the dimension represents
            $isColor = count(array_filter($dims, fn($d) => in_array($d, $colorWords))) > count($dims) / 2;
            $isSize = count(array_filter($dims, fn($d) => in_array($d, $sizeWords))) > count($dims) / 2;

            $newDimType = null;
            if ($isColor && $colorType) $newDimType = $colorType;
            elseif ($isSize && $sizeType) $newDimType = $sizeType;

            if (!$newDimType) {
                $this->warn("Skipping \"{$base}\": cannot determine dimension type for: " . implode(', ', $dims));
                continue;
            }

            $this->info(($dryRun ? '[DRY] ' : '') . "Merging \"{$base}\" (" . count($items) . " groups, dimension: {$newDimType->name})");

            if ($dryRun) {
                foreach ($items as $item) {
                    $g = $groups->find($item['group_id']);
                    $this->line("  Group {$item['group_id']} ({$item['dimension']}): {$g->products->count()} products");
                }
                $merged++;
                continue;
            }

            DB::transaction(function () use ($items, $groups, $newDimType, $base, &$merged) {
                // Keep the first group as the target
                $targetGroupId = $items[0]['group_id'];
                $targetGroup = $groups->find($targetGroupId);

                // Rename group to the common base
                $targetGroup->update(['name' => ucwords($base)]);

                foreach ($items as $item) {
                    $g = $groups->find($item['group_id']);
                    $dimValue = strtoupper($item['dimension']);

                    // Find or create the variation value for the dimension
                    $value = VariationValue::firstOrCreate(
                        ['variation_type_id' => $newDimType->id, 'value' => $dimValue],
                    );

                    foreach ($g->products as $product) {
                        // Move product to target group
                        if ($product->variation_group_id !== $targetGroupId) {
                            $product->update(['variation_group_id' => $targetGroupId]);

                            // Move existing attributes to target group
                            ProductVariationAttribute::where('product_id', $product->id)
                                ->where('variation_group_id', $item['group_id'])
                                ->update(['variation_group_id' => $targetGroupId]);
                        }

                        // Add the new dimension attribute (color, size, etc.)
                        $exists = ProductVariationAttribute::where('product_id', $product->id)
                            ->where('variation_group_id', $targetGroupId)
                            ->where('variation_type_id', $newDimType->id)
                            ->exists();

                        if (!$exists) {
                            ProductVariationAttribute::create([
                                'product_id' => $product->id,
                                'variation_group_id' => $targetGroupId,
                                'variation_type_id' => $newDimType->id,
                                'variation_value_id' => $value->id,
                            ]);
                        }
                    }

                    // Delete the now-empty source group (if not the target)
                    if ($item['group_id'] !== $targetGroupId) {
                        VariationGroup::where('id', $item['group_id'])
                            ->whereDoesntHave('products')
                            ->delete();
                    }
                }

                $merged++;
            });
        }

        $this->newLine();
        $this->info("Done: {$merged} group sets merged.");
        if ($dryRun) {
            $this->warn('Dry run — no changes applied.');
        }

        return self::SUCCESS;
    }
}
