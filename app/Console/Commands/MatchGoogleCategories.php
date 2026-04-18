<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class MatchGoogleCategories extends Command
{
    protected $signature = 'categories:match-google
                            {--dry-run : Show suggestions without writing to DB}
                            {--threshold=70 : Minimum score percentage to auto-fill (0-100)}';

    protected $description = 'Auto-match BO categories to the Google product taxonomy by name similarity';

    private const STOPWORDS = ['and', 'the', 'for', 'with', 'other'];

    public function handle(): int
    {
        $taxonomy = config('google_taxonomy.categories', []);

        if (empty($taxonomy)) {
            $this->error('Google taxonomy config is empty. Check config/google_taxonomy.php.');
            return Command::FAILURE;
        }

        $threshold = (float) $this->option('threshold');
        $dryRun = (bool) $this->option('dry-run');

        $precomputed = [];
        foreach ($taxonomy as $path) {
            $leaf = $this->leafOf($path);
            $precomputed[] = [
                'path'       => $path,
                'pathTokens' => $this->tokenize($path),
                'leaf'       => $leaf,
                'leafTokens' => $this->tokenize($leaf),
            ];
        }

        $categories = Category::with('translations')
            ->whereNull('google_product_category')
            ->get();

        if ($categories->isEmpty()) {
            $this->info('No categories need matching (all already have a Google category).');
            return Command::SUCCESS;
        }

        $tag = $dryRun ? '[DRY-RUN] ' : '';
        $this->info("{$tag}Matching {$categories->count()} categories against " . count($taxonomy) . " Google entries (threshold {$threshold}%).");
        $this->newLine();

        $matched = 0;
        $skipped = 0;

        foreach ($categories as $cat) {
            $trans = $cat->translation('en');
            if (!$trans || !$trans->name) {
                $this->line("  - #{$cat->id}: no EN translation, skipped");
                $skipped++;
                continue;
            }

            $boPath = $cat->fullPathName('en');
            $boPathTokens = $this->tokenize($boPath);
            $boLeaf = $trans->name;
            $boLeafTokens = $this->tokenize($boLeaf);

            $scored = [];
            foreach ($precomputed as $entry) {
                $scored[] = [
                    'score' => $this->score(
                        $boPathTokens, $boLeaf, $boLeafTokens,
                        $entry['pathTokens'], $entry['leaf'], $entry['leafTokens']
                    ),
                    'path'  => $entry['path'],
                ];
            }

            usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
            $top = array_slice($scored, 0, 3);
            $best = $top[0];

            if ($best['score'] >= $threshold) {
                $this->info(sprintf('  OK  #%d [%s] -> %s  (%.1f%%)', $cat->id, $boPath, $best['path'], $best['score']));
                if (!$dryRun) {
                    $cat->update(['google_product_category' => $best['path']]);
                }
                $matched++;
            } else {
                $this->warn(sprintf('  ??  #%d [%s] no confident match (best %.1f%%):', $cat->id, $boPath, $best['score']));
                foreach ($top as $s) {
                    $this->line(sprintf('        %5.1f%%  %s', $s['score'], $s['path']));
                }
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Matched: {$matched}   Skipped: {$skipped}" . ($dryRun ? '   (dry-run — nothing saved)' : ''));

        return Command::SUCCESS;
    }

    private function tokenize(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter(
            $tokens,
            fn($t) => strlen($t) >= 3 && !in_array($t, self::STOPWORDS, true)
        ));
    }

    private function leafOf(string $path): string
    {
        $parts = explode('>', $path);
        return trim(end($parts));
    }

    private function score(
        array $boPathTokens, string $boLeaf, array $boLeafTokens,
        array $gPathTokens, string $gLeaf, array $gLeafTokens
    ): float {
        similar_text(strtolower($boLeaf), strtolower($gLeaf), $leafPct);

        $pathJaccard = $this->jaccard($boPathTokens, $gPathTokens);
        $leafJaccard = $this->jaccard($boLeafTokens, $gLeafTokens);

        $exactLeaf = (strtolower(trim($boLeaf)) === strtolower(trim($gLeaf)));
        $exactBonus = $exactLeaf ? 20 : 0;

        $subsetBonus = 0;
        if (!$exactLeaf && !empty($boLeafTokens) && !empty($gLeafTokens)) {
            $boSet = array_unique($boLeafTokens);
            $gSet = array_unique($gLeafTokens);
            if (!array_diff($boSet, $gSet) || !array_diff($gSet, $boSet)) {
                $subsetBonus = 15;
            }
        }

        $score = ($leafPct * 0.5)
               + ($leafJaccard * 100 * 0.3)
               + ($pathJaccard * 100 * 0.1)
               + $exactBonus
               + $subsetBonus;

        return (float) min($score, 100);
    }

    private function jaccard(array $a, array $b): float
    {
        $a = array_unique($a);
        $b = array_unique($b);
        $union = count(array_unique(array_merge($a, $b)));
        if ($union === 0) {
            return 0.0;
        }
        return count(array_intersect($a, $b)) / $union;
    }
}
