<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixProductSlugs extends Command
{
    protected $signature = 'products:fix-slugs
                            {--dry-run : Afficher les changements sans les appliquer}
                            {--force : Régénérer tous les slugs, même ceux existants}';

    protected $description = 'Génère ou corrige les slugs manquants/vides pour tous les produits (en/fr)';

    protected array $locales = ['en', 'fr'];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('Mode dry-run : aucune modification ne sera effectuée.');
        }

        $products = Product::all();
        $this->info("Analyse de {$products->count()} produits...");

        $fixed = 0;
        $skipped = 0;
        $errors = 0;

        // Collecter tous les slugs existants pour détecter les doublons
        $existingSlugs = [];
        foreach ($products as $product) {
            $slugs = $product->slugs ?? [];
            foreach ($this->locales as $loc) {
                if (!empty($slugs[$loc])) {
                    $existingSlugs[$loc][] = $slugs[$loc];
                }
            }
        }

        foreach ($products as $product) {
            $names = $product->name ?? [];
            $slugs = $product->slugs ?? [];
            $changed = false;
            $changes = [];

            foreach ($this->locales as $loc) {
                $currentSlug = $slugs[$loc] ?? '';
                $name = $names[$loc] ?? '';
                $otherLoc = $loc === 'en' ? 'fr' : 'en';

                // Vérifier si le slug a besoin d'être corrigé
                $needsFix = false;
                $reason = '';

                if ($force) {
                    $needsFix = true;
                    $reason = 'force';
                } elseif (empty($currentSlug)) {
                    $needsFix = true;
                    $reason = 'manquant';
                } elseif ($currentSlug !== Str::slug($currentSlug)) {
                    // Slug mal formé (espaces, majuscules, caractères spéciaux)
                    $needsFix = true;
                    $reason = 'mal formé';
                }

                if (!$needsFix) {
                    continue;
                }

                // Générer le slug à partir du nom
                $newSlug = Str::slug($name);

                if (empty($newSlug)) {
                    // Fallback 1 : utiliser le slug existant de l'autre locale
                    $otherSlug = $slugs[$otherLoc] ?? '';
                    if (!empty($otherSlug)) {
                        $newSlug = $otherSlug;
                    }
                }

                if (empty($newSlug)) {
                    // Fallback 2 : essayer le nom de l'autre locale
                    $otherName = $names[$otherLoc] ?? '';
                    $newSlug = Str::slug($otherName);
                }

                if (empty($newSlug) && !empty($product->ean)) {
                    $newSlug = Str::slug($product->ean);
                }

                if (empty($newSlug)) {
                    $newSlug = 'product-' . $product->id;
                }

                // Garantir l'unicité du slug
                $baseSlug = $newSlug;
                $allSlugs = $existingSlugs[$loc] ?? [];
                $suffix = 1;
                while (in_array($newSlug, $allSlugs)) {
                    // Vérifier si c'est le même produit qui possède déjà ce slug
                    if ($newSlug === $currentSlug) {
                        break;
                    }
                    $newSlug = $baseSlug . '-' . $suffix;
                    $suffix++;
                }

                if ($newSlug === $currentSlug) {
                    continue;
                }

                $slugs[$loc] = $newSlug;
                $changed = true;

                // Enregistrer le nouveau slug dans la liste pour éviter les doublons futurs
                $existingSlugs[$loc][] = $newSlug;

                $changes[] = [
                    'locale' => $loc,
                    'old' => $currentSlug ?: '(vide)',
                    'new' => $newSlug,
                    'reason' => $reason,
                ];
            }

            if (!$changed) {
                $skipped++;
                continue;
            }

            // Afficher les changements
            $productName = $names['fr'] ?? $names['en'] ?? "ID:{$product->id}";
            foreach ($changes as $change) {
                $tag = $dryRun ? '<comment>[DRY-RUN]</comment>' : '<info>[CORRIGÉ]</info>';
                $this->line(
                    "  {$tag} #{$product->id} \"{$productName}\" [{$change['locale']}] " .
                    "{$change['old']} → <fg=green>{$change['new']}</> ({$change['reason']})"
                );
            }

            if (!$dryRun) {
                $product->slugs = $slugs;
                $product->save();
            }

            $fixed++;
        }

        $this->newLine();
        $this->info("Résultat : {$fixed} produits corrigés, {$skipped} OK, {$errors} erreurs.");

        if ($dryRun && $fixed > 0) {
            $this->warn("Relancez sans --dry-run pour appliquer les corrections.");
        }

        return self::SUCCESS;
    }
}
