<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportCategoriesCommand extends Command
{
    protected $signature = 'import:categories {--file=storage/referentiel/referenciel_kabas.xlsx} {--no-translate : Skip automatic translation}';
    protected $description = 'Import categories from Excel file with automatic FR translation';

    private array $categoryCache = [];
    private array $translationCache = [];
    private array $generatedFullSlugs = [];

    public function handle()
    {
        $filePath = storage_path(str_replace('storage/', 'app/', $this->option('file')));

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info('Loading Excel file...');
        $spreadsheet = IOFactory::load($filePath);

        $worksheet = $spreadsheet->getSheetByName('Categories');
        if (!$worksheet) {
            $this->error('Sheet "Categories" not found in the Excel file.');
            return Command::FAILURE;
        }

        $this->info('Clearing existing categories...');
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CategoryTranslation::truncate();
        Category::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->categoryCache = [];
        $this->translationCache = [];

        $this->info('Importing categories...');
        $rows = $worksheet->toArray();
        array_shift($rows); // Ignore header

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $autoTranslate = !$this->option('no-translate');

        foreach ($rows as $row) {
            $mainCategory = trim($row[0] ?? '');
            $subCategory = trim($row[1] ?? '');
            $subSubCategory = trim($row[2] ?? '');

            if (empty($mainCategory)) {
                continue;
            }

            $parent = $this->findOrCreateCategory($mainCategory, null, $autoTranslate);

            if (!empty($subCategory)) {
                $child = $this->findOrCreateCategory($subCategory, $parent->id, $autoTranslate);

                if (!empty($subSubCategory)) {
                    $this->findOrCreateCategory($subSubCategory, $child->id, $autoTranslate);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Import completed successfully!');
        $this->info('Total categories created: ' . Category::count());

        if ($autoTranslate) {
            $this->info('Translations generated for FR locale');
        }

        return Command::SUCCESS;
    }

    private function findOrCreateCategory(string $nameEn, ?int $parentId = null, bool $autoTranslate = true): Category
    {
        $cacheKey = $parentId . '|' . $nameEn;
        if (isset($this->categoryCache[$cacheKey])) {
            return $this->categoryCache[$cacheKey];
        }

        // Vérifie si la catégorie existe déjà en anglais
        $category = Category::whereHas('translations', function ($query) use ($nameEn) {
            $query->where('locale', 'en')->where('name', $nameEn);
        })->where('parent_id', $parentId)->first();

        if (!$category) {
            $category = Category::create(['parent_id' => $parentId]);

            // Traduction anglaise
            $fullSlugEn = $this->generateUniqueFullSlug($category, 'en', $nameEn);
            CategoryTranslation::create([
                'category_id' => $category->id,
                'locale' => 'en',
                'name' => $nameEn,
                'full_slug' => $fullSlugEn
            ]);

            // Traduction française
            if ($autoTranslate) {
                $nameFr = $this->translateToFrench($nameEn);
                if (empty($nameFr)) $nameFr = $nameEn;

                $fullSlugFr = $this->generateUniqueFullSlug($category, 'fr', $nameFr);
                CategoryTranslation::create([
                    'category_id' => $category->id,
                    'locale' => 'fr',
                    'name' => $nameFr,
                    'full_slug' => $fullSlugFr
                ]);
            }
        }

        $this->categoryCache[$cacheKey] = $category;
        return $category;
    }

    private function translateToFrench(string $text): string
    {
        if (isset($this->translationCache[$text])) {
            return $this->translationCache[$text];
        }

        try {
            $response = Http::timeout(5)
                ->get('https://api.mymemory.translated.net/get', [
                    'q' => $text,
                    'langpair' => 'en|fr'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['responseData']['translatedText'])) {
                    $translated = $data['responseData']['translatedText'];
                    $this->translationCache[$text] = $translated;
                    usleep(200000);
                    return $translated;
                }
            }
        } catch (\Exception $e) {
            $this->warn("Translation failed for: {$text}");
        }

        $this->translationCache[$text] = $text;
        return $text;
    }

    private function generateUniqueFullSlug(Category $category, string $locale, string $name): string
    {
        $slug = Str::slug($name);
        $fullSlug = $this->computeFullSlug($category, $locale, $slug);
        $counter = 1;

        while (CategoryTranslation::where('full_slug', $fullSlug)->exists() ||
               in_array($fullSlug, $this->generatedFullSlugs)) {
            $fullSlug = $this->computeFullSlug($category, $locale, $slug . '-' . $counter++);
        }

        $this->generatedFullSlugs[] = $fullSlug;
        return $fullSlug;
    }

    private function computeFullSlug(Category $category, string $locale, string $slug): string
    {
        $parts = [$slug];
        $parent = $category->parent;

        while ($parent) {
            $parentTranslation = $parent->translations()->where('locale', $locale)->first();
            if ($parentTranslation) {
                array_unshift($parts, $parentTranslation->name); // On utilise le nom traduit pour le full_slug
            }
            $parent = $parent->parent;
        }

        array_unshift($parts, $locale); // Ajout de la langue au début
        $parts = array_map(fn($p) => Str::slug($p), $parts); // Slugify chaque segment
        return implode('/', $parts);
    }
}
