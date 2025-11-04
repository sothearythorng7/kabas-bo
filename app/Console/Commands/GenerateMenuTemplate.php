<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class GenerateMenuTemplate extends Command
{
    protected $signature = 'menu:generate
        {--locales=fr,en : Liste des locales séparées par des virgules}
        {--output=/var/www/kabas-site/resources/views/partials : Dossier cible dans le projet site}
        {--filename=main-nav_%s.blade.php : Modèle de nom de fichier (utilise %s pour la locale)}
        {--max-columns=6 : Nombre max de colonnes par mega-menu}';

    protected $description = 'Génère les partials Blade du menu à partir des catégories du BO';

    public function handle(): int
    {
        $locales = array_filter(array_map('trim', explode(',', (string)$this->option('locales'))));
        $outputDir = rtrim((string)$this->option('output'), '/');
        $filenamePattern = (string)$this->option('filename');
        $maxColumns = (int)$this->option('max-columns');

        if (empty($locales)) {
            $this->error('Aucune locale fournie.');
            return self::FAILURE;
        }

        if (!File::isDirectory($outputDir)) {
            $this->warn("Le dossier cible n'existe pas: $outputDir — création…");
            File::makeDirectory($outputDir, 0755, true);
        }

        // Charge l’arbre en une fois (évite N+1)
        $roots = Category::query()
            ->whereNull('parent_id')
            ->with([
                'translations',
                'children.translations',
                'children.children.translations',
            ])
            ->orderBy('id') // ajuste si tu as un champ "position"
            ->get();

        foreach ($locales as $locale) {
            $html = $this->buildMenuHtml($roots, $locale, $maxColumns);

            $target = $outputDir . '/' . sprintf($filenamePattern, $locale);
            $tmp = $target . '.tmp';

            // Écriture atomique
            File::put($tmp, $html);
            @chmod($tmp, 0644);
            rename($tmp, $target);

            $this->info("✅ Menu généré pour [$locale] → $target");
        }

        return self::SUCCESS;
    }

    /**
     * Construit le HTML complet du menu pour une locale donnée.
     */
    protected function buildMenuHtml($roots, string $locale, int $maxColumns): string
    {
        $e = fn($s) => e($s);

        $ul = [];
        $ul[] = '<nav class="main-nav" id="mainNav">';
        $ul[] = '  <div class="container">';
        $ul[] = '    <ul class="nav-menu">';

        // Lien HOME (adaptable)
        $siteUrl = rtrim(config('app.site_public_url', 'http://localhost'), '/');
        $homeUrl = $siteUrl . '/';
        $ul[] = '      <li><a href="'.$this->e($homeUrl).'">HOME</a></li>';

        foreach ($roots as $root) {
            $rootName = $this->transName($root, $locale);
            $rootSlug = $this->fullSlug($root, $locale);
            $rootUrl  = $this->categoryUrl($locale, $rootSlug);

            $children = $root->children ?? collect();
            $hasCols = $children->isNotEmpty();

            $ul[] = '      <li class="has-mega-menu">';
            $ul[] = '        <a href="'.$this->e($rootUrl).'">'.mb_strtoupper($this->e($rootName)).'</a>';

            if ($hasCols) {
                // Équilibrage simple du nombre de colonnes (limité par --max-columns)
                $columns = $children->values();
                if ($columns->count() > $maxColumns) {
                    $columns = $columns->slice(0, $maxColumns);
                }

                $ul[] = '        <div class="mega-menu">';
                $ul[] = '          <div class="mega-menu-content">';

                foreach ($columns as $col) {
                    $colName = $this->transName($col, $locale);
                    $colSlug = $this->fullSlug($col, $locale);
                    $colUrl  = $this->categoryUrl($locale, $colSlug);

                    $ul[] = '            <div class="mega-menu-column">';
                    $ul[] = '              <h3><a href="'.$this->e($colUrl).'">'.$this->e($colName).'</a></h3>';

                    $leafs = $col->children ?? collect();
                    if ($leafs->isNotEmpty()) {
                        $ul[] = '              <ul>';
                        foreach ($leafs as $leaf) {
                            $leafName = $this->transName($leaf, $locale);
                            $leafSlug = $this->fullSlug($leaf, $locale);
                            $leafUrl  = $this->categoryUrl($locale, $leafSlug);
                            $ul[] = '                <li><a href="'.$this->e($leafUrl).'">'.$this->e($leafName).'</a></li>';
                        }
                        $ul[] = '              </ul>';
                    } else {
                        // Si pas de 3e niveau, on répète le col en lien unique
                        $ul[] = '              <ul>';
                        $ul[] = '                <li><a href="'.$this->e($colUrl).'">'.$this->e($colName).'</a></li>';
                        $ul[] = '              </ul>';
                    }

                    $ul[] = '            </div>';
                }

                $ul[] = '          </div>';
                $ul[] = '        </div>';
            }

            $ul[] = '      </li>';
        }

        $ul[] = '    </ul>';
        $ul[] = '  </div>';
        $ul[] = '</nav>';

        return implode("\n", $ul);
    }

    protected function transName($category, string $locale): string
    {
        $t = $category->translations->firstWhere('locale', $locale)
            ?? $category->translations->first();
        return $t->name ?? '—';
    }

    /**
     * Si tu as une colonne `full_slug` en base (table category_translations), prends-la.
     * Sinon, concatène récursivement les slugs.
     */
    protected function fullSlug($category, string $locale): string
    {
        $t = $category->translations->firstWhere('locale', $locale);
        if ($t && !empty($t->full_slug)) {
            return trim($t->full_slug, '/');
        }

        // Fallback: reconstruit depuis la hiérarchie via translations->slug
        $slugs = [];
        $node = $category;
        while ($node) {
            $tt = $node->translations->firstWhere('locale', $locale)
                ?? $node->translations->first();
            $slug = $tt->slug ?? $tt->name ?? '';
            $slugs[] = trim(strtolower(\Str::slug($slug)), '/');
            $node = $node->parent ?? null; // ATTENTION: parent non eager-loaded ici
        }
        $slugs = array_reverse(array_filter($slugs));
        return implode('/', $slugs);
    }

    protected function categoryUrl(string $locale, string $fullSlug): string
    {
        // Le full_slug en base contient la locale (ex: fr/delices-cambodgiens)
        // Mais la route attend: {locale}/c/{slug}
        // Donc on retire le préfixe locale du fullSlug si présent
        $slug = $fullSlug;
        if (str_starts_with($slug, $locale . '/')) {
            $slug = substr($slug, strlen($locale) + 1);
        }

        $siteUrl = rtrim(config('app.site_public_url', 'http://localhost'), '/');
        return $siteUrl . '/' . $this->e($locale) . '/c/' . $this->e($slug);
    }

    protected function e(string $value): string
    {
        return e($value);
    }
}
