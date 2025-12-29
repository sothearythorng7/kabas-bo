<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSaleTotals extends Command
{
    protected $signature = 'sales:fix-totals {--dry-run : Show what would be changed without making changes}';
    protected $description = 'Recalculate and fix sale totals to reflect the correct amount after discounts';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN - No changes will be made');
        }

        $sales = Sale::with('items', 'financialTransaction')->get();
        $fixed = 0;
        $transactionsFixed = 0;

        foreach ($sales as $sale) {
            $correctTotal = $this->calculateCorrectTotal($sale);

            // Check if sale total needs fixing
            $saleTotalNeedsFix = abs($sale->total - $correctTotal) > 0.01;

            // Check if transaction amount needs fixing
            $transactionNeedsFix = $sale->financialTransaction
                && abs($sale->financialTransaction->amount - $correctTotal) > 0.01;

            if ($saleTotalNeedsFix || $transactionNeedsFix) {
                $this->line("Sale #{$sale->id}:");
                $this->line("  Items gross: " . $sale->items->sum(fn($i) => $i->price * $i->quantity));
                $this->line("  Discounts: " . ($sale->items->sum(fn($i) => $i->price * $i->quantity) - $correctTotal));
                $this->line("  Correct total: {$correctTotal}");

                if ($saleTotalNeedsFix) {
                    $this->line("  Sale total: {$sale->total} -> {$correctTotal}");
                }

                if ($transactionNeedsFix) {
                    $this->line("  Transaction amount: {$sale->financialTransaction->amount} -> {$correctTotal}");
                }

                if (!$dryRun) {
                    DB::transaction(function () use ($sale, $correctTotal, $saleTotalNeedsFix, $transactionNeedsFix, &$fixed, &$transactionsFixed) {
                        if ($saleTotalNeedsFix) {
                            $sale->update(['total' => $correctTotal]);
                            $fixed++;
                        }

                        if ($transactionNeedsFix) {
                            // Recalculate balance_after based on the difference
                            $difference = $correctTotal - $sale->financialTransaction->amount;
                            $sale->financialTransaction->update([
                                'amount' => $correctTotal,
                                'balance_after' => $sale->financialTransaction->balance_after + $difference,
                            ]);
                            $transactionsFixed++;
                        }
                    });
                }
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Would fix {$fixed} sale totals and {$transactionsFixed} transaction amounts");
        } else {
            $this->info("Fixed {$fixed} sale totals and {$transactionsFixed} transaction amounts");
        }

        return 0;
    }

    private function calculateCorrectTotal(Sale $sale): float
    {
        $itemsTotal = 0;
        $totalDiscounts = 0;

        foreach ($sale->items as $item) {
            $lineGross = $item->price * $item->quantity;
            $itemsTotal += $lineGross;

            foreach ($item->discounts ?? [] as $d) {
                if ($d['type'] === 'amount') {
                    if (($d['scope'] ?? 'line') === 'unit') {
                        $totalDiscounts += $d['value'] * $item->quantity;
                    } else {
                        $totalDiscounts += $d['value'];
                    }
                } elseif ($d['type'] === 'percent') {
                    $totalDiscounts += ($d['value'] / 100) * $lineGross;
                }
            }
        }

        foreach ($sale->discounts ?? [] as $d) {
            if ($d['type'] === 'amount') {
                $totalDiscounts += $d['value'];
            } elseif ($d['type'] === 'percent') {
                $totalDiscounts += ($d['value'] / 100) * $itemsTotal;
            }
        }

        return round($itemsTotal - $totalDiscounts, 2);
    }
}
