<?php

namespace App\Console\Commands;

use App\Models\VariationValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupVariationValueUnits extends Command
{
    protected $signature = 'variation-values:cleanup-units {--apply : Actually update rows (otherwise dry-run)}';
    protected $description = 'Extract "25G" from values like "25G RECYCLE PAPER" for weight/capacity types (safe: only touches values containing a number+unit).';

    private const UNIT_PATTERN = '/\b(\d+(?:[.,]\d+)?)\s*(KG|KGR|MG|G|GR|GRAM|GRAMS|GRAMME|GRAMMES|CL|ML|L|LITER|LITERS|LITRE|LITRES)\b/i';

    private const TARGET_TYPES = ['weight', 'capacity', 'size'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $rows = VariationValue::with('type')->get()->filter(function ($v) {
            $name = strtolower($v->type->name ?? '');
            return in_array($name, self::TARGET_TYPES, true);
        });

        $changes = [];
        $skipped = [];

        foreach ($rows as $v) {
            $original = $v->value;
            $trimmed = trim($original);

            if (preg_match('/^\s*\d+(?:[.,]\d+)?\s*(kg|kgr|mg|g|gr|gram|grams|gramme|grammes|cl|ml|l|liter|liters|litre|litres)\s*$/i', $trimmed)) {
                continue; // already clean
            }

            if (preg_match(self::UNIT_PATTERN, $trimmed, $m)) {
                $clean = str_replace(',', '.', $m[1]) . strtoupper($m[2]);
                if ($clean !== $trimmed) {
                    $changes[] = ['row' => $v, 'from' => $original, 'to' => $clean];
                }
            } else {
                // Only list "size" as skipped if it doesn't look like pure text we shouldn't touch;
                // weight/capacity with no unit = misplaced type, not our problem here.
                if (strtolower($v->type->name) !== 'size') {
                    $skipped[] = $original . ' [' . $v->type->name . ']';
                }
            }
        }

        if (empty($changes) && empty($skipped)) {
            $this->info('Nothing to clean.');
            return self::SUCCESS;
        }

        // Detect collisions: cleaned value already exists as another row under the same type
        foreach ($changes as &$c) {
            $typeId = $c['row']->variation_type_id;
            $target = VariationValue::where('variation_type_id', $typeId)
                ->where('value', $c['to'])
                ->where('id', '!=', $c['row']->id)
                ->first();
            $c['merge_into'] = $target?->id;
        }
        unset($c);

        if (!empty($changes)) {
            $this->line('');
            $this->info('Changes (' . count($changes) . '):');
            foreach ($changes as $c) {
                $action = $c['merge_into']
                    ? 'merge into existing id=' . $c['merge_into']
                    : 'rename';
                $this->line(sprintf('  [id=%d] %-45s → %-10s (%s)', $c['row']->id, '"' . $c['from'] . '"', $c['to'], $action));
            }
        }

        if (!empty($skipped)) {
            $this->line('');
            $this->warn('Cannot auto-fix (no number+unit detected, probably wrong type):');
            foreach ($skipped as $s) {
                $this->line('  ' . $s);
            }
        }

        if (!$apply) {
            $this->line('');
            $this->comment('Dry-run. Use --apply to commit the changes above.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $c) {
                $oldId = $c['row']->id;
                if ($c['merge_into']) {
                    DB::table('product_variation_attributes')
                        ->where('variation_value_id', $oldId)
                        ->update(['variation_value_id' => $c['merge_into']]);
                    $c['row']->delete();
                } else {
                    $c['row']->value = $c['to'];
                    $c['row']->save();
                }
            }
        });

        $this->line('');
        $this->info('Applied ' . count($changes) . ' update(s).');
        return self::SUCCESS;
    }
}
