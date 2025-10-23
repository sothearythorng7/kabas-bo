<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\StockBatch;
use App\Models\VariationType;
use App\Models\VariationValue;
use App\Models\ProductVariation;

class ImportProductsCommand extends Command
{
    protected $signature = 'import:products {--file=storage/referentiel/referenciel_kabas.xlsx}';
    protected $description = 'Import Products from Excel file';
    private $categoriesCache = [];

    public function handle()
    {
        // Cache catégories FR/EN avec normalisation des chemins
        $this->categoriesCache = $this->buildAllCategoryPathsMultiLocale();

        $filePath = storage_path(str_replace('storage/', 'app/', $this->option('file')));
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info('Loading Excel file...');
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getSheetByName('Produits');
        if (!$worksheet) {
            $this->error('Sheet "Produits" not found in the Excel file.');
            return Command::FAILURE;
        }

        $this->info('Clearing existing products...');
        // Désactiver les vérifications de clés étrangères
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Product::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $rows = $worksheet->toArray();
        array_shift($rows); // remove header
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $stores = Store::all();
        $locales = config('app.website_locales', ['en']);

        // Regroupement: baseKey => [ ['product'=>Product, 'vars'=>['color'=>..,'size'=>..,'capacity'=>..,'weight'=>..]] ]
        $groups = [];

        foreach ($rows as $row) {
            [$id, $ean, $name, $color, $size, $brandName, $supplier1, $supplier2, $category1, $category2, $costPrice, $salePrice, $description] = $row;
            $name = trim($name ?? '');
            if (!$name) continue;

            // If EAN is empty, generate a random string
            if (!$ean) {
                $ean = 'EAN' . strtoupper(Str::random(10));
            }

            DB::transaction(function() use ($ean, $name, $color, $size, $brandName, $supplier1, $supplier2, $category1, $category2, $costPrice, $salePrice, $description, $stores, $locales, &$groups) {

                // -------------------------
                // Brand
                $brand = $brandName ? Brand::firstOrCreate(['name' => trim($brandName)]) : null;

                // -------------------------
                // Product
                $product = Product::firstOrCreate(
                    ['ean' => trim($ean)],
                    [
                        'price' => $this->parsePrice($salePrice),
                        'price_btob' => $this->parsePrice($costPrice),
                        'brand_id' => $brand?->id,
                        'color' => null, // ne plus stocker directement
                        'size' => null,  // ne plus stocker directement
                        'is_active' => true,
                        'is_best_seller' => false,
                        'is_resalable' => false,
                        'name' => ['en' => "***", "fr" => "***"],
                        'description' => ['en' => "***", "fr" => "***"],
                        'slugs' => "***",
                    ]
                );

                // -------------------------
                // Multilingual fields
                foreach ($locales as $locale) {
                    $product->setTranslation('name', $locale, $name);
                    $product->setTranslation('description', $locale, $description ?? '');
                    $product->setTranslation('slugs', $locale, Str::slug($name));
                }
                $product->save();

                // -------------------------
                // Category attachment (exact sync, FR/EN, normalisation des séparateurs)
                $catIds = [];
                foreach ([$category1, $category2] as $catRaw) {
                    if (!is_string($catRaw) || trim($catRaw) === '') continue;
                    $norm = $this->normalizePath(trim($catRaw));
                    if ($norm === '') continue;

                    $catId = $this->categoriesCache[$norm] ?? null;
                    if ($catId) {
                        $catIds[$catId] = $catId; // set unique
                    } else {
                        $this->warn("Catégorie introuvable: '{$catRaw}' (normalisée: '{$norm}')");
                    }
                }
                // Remplacer exactement les catégories par celles trouvées (détache les anciennes mauvaises)
                $product->categories()->sync(array_values($catIds));

                // -------------------------
                // Suppliers
                $supplierIds = [];
                foreach ([$supplier1, $supplier2] as $supName) {
                    if ($supName) {
                        $sup = Supplier::where('name', trim($supName))->first();
                        if ($sup) $supplierIds[$sup->id] = ['purchase_price' => $this->parsePrice($costPrice)];
                    }
                }
                $product->suppliers()->syncWithoutDetaching($supplierIds);

                // -------------------------
                // Initial Stock
                foreach ($stores as $store) {
                    StockBatch::firstOrCreate(
                        ['product_id' => $product->id, 'store_id' => $store->id],
                        ['quantity' => 100, 'unit_price' => $this->parsePrice($costPrice), 'label' => 'Initial import']
                    );
                }

                // -------------------------
                // Variations (types + values) -> via ProductVariation, lié au même produit
                // Candidats issus des colonnes
                $sizeInfo = $this->resolveSizeVariation($size); // size/capacity/weight (normalisés + équivalences)
                $colorFromColumn = $color && trim($color) ? strtoupper(trim($color)) : null;

                // Extraction supplémentaire depuis le nom si size vide / et pour couleurs
                $fromName = $this->resolveVariationsFromName($name); // ['size'=>..., 'capacity'=>..., 'weight'=>..., 'color'=>STRING]

                // 1) COULEUR (priorité colonne, sinon nom) — SELF LINK avec guard d'existence
                $finalColor = $colorFromColumn ?: ($fromName['color'] ?? null);
                $colVar = null;
                if ($finalColor) {
                    $colorType = $this->getOrCreateVariationType('color', 'Color');
                    $colorValModel = VariationValue::firstOrCreate([
                        'variation_type_id' => $colorType->id,
                        'value' => $finalColor,
                    ]);

                    // éviter le doublon unique (product_id, variation_value_id)
                    $exists = ProductVariation::where('product_id', $product->id)
                        ->where('variation_value_id', $colorValModel->id)
                        ->exists();

                    if (!$exists) {
                        ProductVariation::create([
                            'product_id'         => $product->id,
                            'linked_product_id'  => $product->id,   // self
                            'variation_type_id'  => $colorType->id,
                            'variation_value_id' => $colorValModel->id,
                        ]);
                    }

                    $colVar = ['type_id' => $colorType->id, 'value_id' => $colorValModel->id];
                }

                // 2) TAILLE / CAPACITÉ / POIDS — SELF LINK avec guard d'existence
                $resolved = $sizeInfo;
                if (!$resolved) {
                    if (!empty($fromName['capacity'])) {
                        $resolved = $fromName['capacity'];
                    } elseif (!empty($fromName['weight'])) {
                        $resolved = $fromName['weight'];
                    } elseif (!empty($fromName['size'])) {
                        $resolved = $fromName['size'];
                    }
                }

                $vars = [
                    'color'    => $colVar,
                    'size'     => null,
                    'capacity' => null,
                    'weight'   => null,
                ];

                if ($resolved) {
                    $type = $this->getOrCreateVariationType($resolved['type'], $resolved['label']);
                    $valModel = VariationValue::firstOrCreate([
                        'variation_type_id' => $type->id,
                        'value' => $resolved['value'],
                    ]);

                    $exists = ProductVariation::where('product_id', $product->id)
                        ->where('variation_value_id', $valModel->id)
                        ->exists();

                    if (!$exists) {
                        ProductVariation::create([
                            'product_id'         => $product->id,
                            'linked_product_id'  => $product->id,   // self
                            'variation_type_id'  => $type->id,
                            'variation_value_id' => $valModel->id,
                        ]);
                    }

                    // ranger dans la bonne clé ('size' | 'capacity' | 'weight')
                    $vars[$resolved['type']] = ['type_id' => $type->id, 'value_id' => $valModel->id];
                }

                // -------------------------
                // Regroupement par base de nom (on retire couleurs/tailles/poids/volumes du nom)
                $baseKey = $this->makeBaseKeyFromName($name);
                $groups[$baseKey][] = [
                    'product' => $product,
                    'vars'    => $vars,
                ];
            });

            $bar->advance();
        }

        // -------------------------
        // Deuxième passe : liaisons croisées PAR AXE avec contraintes sur les autres axes
        // IMPORTANT : on NE réinsère PAS dans product_variations (sinon unicité saute).
        // On lie les produits via product_variation_links (relatedProducts()).
        $this->info("\nLinking cross-variations between grouped products (by axis)...");
        $AXES = ['color','size','capacity','weight'];

        foreach ($groups as $baseKey => $items) {
            $n = count($items);
            if ($n <= 1) continue;

            // Index par axe : clé = valeurs des autres axes => liste d'items compatibles
            $indexByAxis = [];
            foreach ($AXES as $axis) {
                $indexByAxis[$axis] = [];
            }

            $keyFor = function(array $vars, string $axis) use ($AXES) {
                // Construit une clé figeant les autres axes (value_id ou 0 si null)
                $parts = [];
                foreach ($AXES as $ax) {
                    if ($ax === $axis) continue;
                    $vid = $vars[$ax]['value_id'] ?? 0; // 0 = non défini/absent
                    $parts[] = $ax . ':' . $vid;
                }
                return implode('|', $parts);
            };

            // Remplir l’index
            foreach ($items as $item) {
                $vars = $item['vars'];
                foreach ($AXES as $axis) {
                    $k = $keyFor($vars, $axis);
                    $indexByAxis[$axis][$k] = $indexByAxis[$axis][$k] ?? [];
                    $indexByAxis[$axis][$k][] = $item;
                }
            }

            // Pour chaque axe: relier entre eux les items qui partagent les autres axes identiques
            foreach ($AXES as $axis) {
                foreach ($indexByAxis[$axis] as $k => $bucket) {
                    if (count($bucket) <= 1) continue;

                    foreach ($bucket as $A) {
                        $prodA = $A['product'];
                        $valA  = $A['vars'][$axis]['value_id'] ?? null;

                        foreach ($bucket as $B) {
                            if ($A === $B) continue;
                            $prodB = $B['product'];
                            $varB  = $B['vars'][$axis] ?? null;
                            if (!$varB) continue; // B n’a pas de valeur sur cet axe
                            if ($valA && $varB['value_id'] === $valA) continue; // même valeur => inutile

                            // LIAISON PRODUIT <-> PRODUIT (pas d'insertion dans product_variations)
                            $prodA->relatedProducts()->syncWithoutDetaching([$prodB->id]);
                            // (Optionnel) Forcer la symétrie si nécessaire
                            // $prodB->relatedProducts()->syncWithoutDetaching([$prodA->id]);
                        }
                    }
                }
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Import completed successfully!');
        $this->info('Total products: ' . Product::count());

        return Command::SUCCESS;
    }

    private function parsePrice($price)
    {
        if (!$price) return 0;
        return floatval(str_replace([',','$',' '], ['.','',''], $price));
    }

    /**
     * Détermine type/label/value pour "size" selon les règles (avec normalisation):
     * - Size textile -> type=size  value=XS/S/M/L/XL/2XL/3XL (XXL=>2XL, XXXL=>3XL)
     * - Capacity -> normalisée en CL (ex: 1 L -> 100 CL, 500 ml -> 50 CL)
     * - Weight   -> normalisée en G  (ex: 1 KG -> 1000 G)
     * Retourne null si vide/inexploitable.
     */
    private function resolveSizeVariation(?string $raw)
    {
        if (!$raw || !trim($raw)) return null;
        $s = trim($raw);

        // 1) Taille textile
        if ($this->isTextileSize($s)) {
            return [
                'type'  => 'size',
                'label' => 'Size',
                'value' => $this->normalizeTextileSize($s), // inclut XXL=>2XL, XXXL=>3XL
            ];
        }

        // 2) Capacity (L/CL/ML) -> normalisation CL
        if (preg_match('/^\s*([\d]+(?:[.,]\d+)?)\s*(cl|ml|l)\s*$/i', $s, $m)) {
            [$num, $unit] = [$m[1], strtoupper($m[2])];
            $cl = $this->toCl($num, $unit);
            return [
                'type'  => 'capacity',
                'label' => 'Capacity',
                'value' => $cl . ' CL',
            ];
        }

        // 3) Weight (KG/G) -> normalisation G
        if (preg_match('/^\s*([\d]+(?:[.,]\d+)?)\s*(kg|g)\s*$/i', $s, $m)) {
            [$num, $unit] = [$m[1], strtoupper($m[2])];
            $g = $this->toG($num, $unit);
            return [
                'type'  => 'weight',
                'label' => 'Weight',
                'value' => $g . ' G',
            ];
        }

        // 4) Sinon : taille libre => size (majuscules) + équivalences si applicable
        $free = strtoupper($s);
        $free = $this->normalizeTextileEquivalence($free);
        return [
            'type'  => 'size',
            'label' => 'Size',
            'value' => $free,
        ];
    }

    /**
     * Extraction complète depuis le nom du produit.
     * Renvoie un tableau associatif de candidats: ['size'=>[...]] / ['capacity'=>[...]] / ['weight'=>[...]] / ['color'=>STRING]
     * Couleurs multiples -> "COLOR1-COLOR2(-COLOR3...)" (ordre d’apparition, uniq).
     */
    private function resolveVariationsFromName(string $name): array
    {
        $out = [];
        $s = trim($name);
        if ($s === '') return $out;

        // Capacity -> CL
        if (preg_match('/\b([\d]+(?:[.,]\d+)?)\s*(ml|cl|l)\b/i', $s, $m)) {
            $out['capacity'] = [
                'type'  => 'capacity',
                'label' => 'Capacity',
                'value' => $this->toCl($m[1], strtoupper($m[2])) . ' CL',
            ];
        }

        // Weight -> G
        if (preg_match('/\b([\d]+(?:[.,]\d+)?)\s*(kg|g)\b/i', $s, $m)) {
            $out['weight'] = [
                'type'  => 'weight',
                'label' => 'Weight',
                'value' => $this->toG($m[1], strtoupper($m[2])) . ' G',
            ];
        }

        // Taille textile (normalisée XXL=>2XL, XXXL=>3XL)
        if (preg_match('/\b(?:XS|S|M|L|XL|XXL|XXXL|2XL|3XL)\b/i', $s, $m)) {
            $out['size'] = [
                'type'  => 'size',
                'label' => 'Size',
                'value' => $this->normalizeTextileSize($m[0]),
            ];
        }

        // Couleurs/thèmes : TOUTES les occurrences, jointes par '-'
        $colorsFound = $this->extractAllColorsFromName($s);
        if (!empty($colorsFound)) {
            $out['color'] = implode('-', $colorsFound);
        }

        // Segments après tirets (ex: WHITE-M)
        $parts = preg_split('/\s*-\s*/', $s);
        if ($parts && count($parts) > 1) {
            $last = end($parts);

            if ($this->isTextileSize($last)) {
                $out['size'] = [
                    'type'  => 'size',
                    'label' => 'Size',
                    'value' => $this->normalizeTextileSize($last),
                ];
            }

            $extraColors = $this->extractAllColorsFromName($last);
            if (!empty($extraColors)) {
                $out['color'] = implode('-', array_values(array_unique(array_merge(
                    isset($out['color']) ? explode('-', $out['color']) : [],
                    $extraColors
                ))));
            }
        }

        return $out;
    }

    // ========= Regroupement par base de nom =========

    /**
     * Construit une clé "base" à partir du nom : on retire couleurs connues, tailles, poids et volumes,
     * signes (-,:), et on normalise espaces/casse.
     */
    private function makeBaseKeyFromName(string $name): string
    {
        $u = ' ' . strtoupper($name) . ' ';

        // Retirer patterns quantité+unité (capacity/weight)
        $u = preg_replace('/\b[\d]+(?:[.,]\d+)?\s*(ML|CL|L|KG|G)\b/i', ' ', $u);

        // Retirer tailles textiles (incluant équivalences)
        $u = preg_replace('/\b(?:XS|S|M|L|XL|XXL|XXXL|2XL|3XL)\b/i', ' ', $u);

        // Retirer toutes les couleurs connues
        $colors = $this->knownColors();
        if (!empty($colors)) {
            $pat = '/\b(?:' . implode('|', array_map('preg_quote', $colors)) . ')\b/i';
            $u = preg_replace($pat, ' ', $u);
        }

        // Remplacer séparateurs par espace
        $u = str_replace(['-', ':', '/'], ' ', $u);

        // Nettoyage espaces
        $u = preg_replace('/\s+/', ' ', $u);
        $u = trim($u);

        return $u;
    }

    // ========= Normalisations & helpers =========

    /** Liste de couleurs/thèmes issue du référentiel + compléments usuels */
    private function knownColors(): array
    {
        // DÉRIVÉ du référentiel fourni + couleurs usuelles + thèmes vus (BEACH, STAR…)
        return [
            'WHITE','BLACK','GREY','GRAY','BROWN','NAVY','BLUE','SKY','TEAL','CYAN','TURQUOISE',
            'FUCHSIA','MAGENTA','RED','MAROON','PINK','SALMON','ORANGE','YELLOW','GOLD','AMBER',
            'IVORY','CREAM','BEIGE','KHAKI','OLIVE','GREEN','LIME','MINT','PURPLE','VIOLET',
            'LAVENDER','INDIGO','SILVER','BRONZE','COPPER',
            // Thèmes/couleurs rencontrés
            'BEACH','STAR'
        ];
    }

    /** Extrait TOUTES les couleurs connues depuis une chaîne, ordre d’apparition, uniq */
    private function extractAllColorsFromName(string $s): array
    {
        $colors = $this->knownColors();
        $tokens = preg_split('/[\s\-:]+/', strtoupper($s));
        $found = [];
        $seen = [];
        foreach ($tokens as $t) {
            if (in_array($t, $colors, true) && !isset($seen[$t])) {
                $found[] = $t;
                $seen[$t] = true;
            }
        }
        return $found;
    }

    /** true si la chaîne correspond à une taille textile (XS/S/M/L/XL/XXL/XXXL/2XL/3XL) */
    private function isTextileSize(string $s): bool
    {
        $t = strtoupper(str_replace(['.', '-', ' '], '', $s));
        return (bool) preg_match('/^(?:XS|S|M|L|XL|XXL|XXXL|2XL|3XL)$/', $t);
    }

    /** normalise taille textile : MAJ + équivalences (XXL=>2XL, XXXL=>3XL) */
    private function normalizeTextileSize(string $s): string
    {
        $norm = strtoupper(str_replace([' ', '.', '-'], '', trim($s)));
        return $this->normalizeTextileEquivalence($norm);
    }

    /** applique les équivalences de tailles */
    private function normalizeTextileEquivalence(string $size): string
    {
        $map = [
            'XXL'  => '2XL',
            'XXXL' => '3XL',
        ];
        return $map[$size] ?? $size;
    }

    /** Convertit num/unit (ML/CL/L) en entier CL */
    private function toCl($num, string $unit): int
    {
        $v = floatval(str_replace(',', '.', (string)$num));
        switch (strtoupper($unit)) {
            case 'L':  $cl = $v * 100; break;     // 1 L = 100 CL
            case 'CL': $cl = $v; break;
            case 'ML': $cl = $v * 0.1; break;     // 10 ML = 1 CL
            default:   $cl = $v; break;
        }
        return (int) round($cl);
    }

    /** Convertit num/unit (KG/G) en entier G */
    private function toG($num, string $unit): int
    {
        $v = floatval(str_replace(',', '.', (string)$num));
        switch (strtoupper($unit)) {
            case 'KG': $g = $v * 1000; break;
            case 'G':  $g = $v; break;
            default:   $g = $v; break;
        }
        return (int) round($g);
    }

    /** Récupère/crée un type de variation avec label */
    private function getOrCreateVariationType(string $name, string $label): \App\Models\VariationType
    {
        return VariationType::firstOrCreate(
            ['name' => $name],
            ['label' => $label]
        );
    }

    // ========= Catégories FR/EN & normalisation de chemin =========

    /**
     * Normalise un chemin libre en "A - B - C" (trim, collapse espaces, accepte '>' ou '-')
     */
    private function normalizePath(string $raw): string
    {
        if ($raw === '') return '';
        // Remplacer '>' ou '-' entourés d'espaces par ' - '
        $s = preg_replace('/\s*[>\-]\s*/u', ' - ', $raw);
        // Réduire espaces multiples
        $s = preg_replace('/\s+/u', ' ', $s);
        // Trim des espaces et tirets résiduels
        $s = trim($s, " \t\n\r\0\x0B-");
        return $s;
    }

    /**
     * Chemin complet d'une catégorie dans une locale donnée
     */
    private function getCategoryFullPathLocale(\App\Models\Category $category, string $locale): string
    {
        $translation = $category->translations->firstWhere('locale', $locale);
        $name = $translation?->name ?? '—';
        if ($category->parent) {
            return $this->getCategoryFullPathLocale($category->parent, $locale) . ' - ' . $name;
        }
        return $name;
    }

    /**
     * Construit un cache multi-locale: clé normalisée -> category_id
     * clés couvertes: EN et FR, avec normalisation des séparateurs
     */
    private function buildAllCategoryPathsMultiLocale(): array
    {
        $categories = \App\Models\Category::with(
            'translations',
            'parent.translations',
            'parent.parent.translations'
        )->get();

        $map = [];
        foreach ($categories as $cat) {
            foreach (['en','fr'] as $loc) {
                $path = $this->getCategoryFullPathLocale($cat, $loc);
                $norm = $this->normalizePath($path);
                if ($norm !== '') {
                    $map[$norm] = $cat->id;
                }
            }
        }
        return $map;
    }
}
