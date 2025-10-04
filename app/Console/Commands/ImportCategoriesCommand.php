<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Http;

class ImportCategoriesCommand extends Command
{
    protected $signature = 'import:categories {--file=storage/referentiel/referenciel_kabas.xlsx} {--no-translate : Skip automatic translation}';
    protected $description = 'Import categories from Excel file with automatic FR translation';

    private array $categoryCache = [];
    private array $translationCache = [];

    public function handle()
    {
        $filePath = storage_path(str_replace('storage/', 'app/', $this->option('file')));

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info('Loading Excel file...');
        $spreadsheet = IOFactory::load($filePath);
        
        // Récupérer l'onglet "Categories"
        $worksheet = $spreadsheet->getSheetByName('Categories');
        
        if (!$worksheet) {
            $this->error('Sheet "Categories" not found in the Excel file.');
            return Command::FAILURE;
        }

        $this->info('Clearing existing categories...');
        
        // Désactiver les vérifications de clés étrangères
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CategoryTranslation::truncate();
        Category::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->categoryCache = [];
        $this->translationCache = [];

        $this->info('Importing categories...');
        $rows = $worksheet->toArray();
        
        // Ignorer la première ligne (en-tête)
        array_shift($rows);

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

            // Créer la catégorie principale
            $parent = $this->findOrCreateCategory($mainCategory, null, $autoTranslate);

            // Créer la sous-catégorie si elle existe
            if (!empty($subCategory)) {
                $child = $this->findOrCreateCategory($subCategory, $parent->id, $autoTranslate);

                // Créer la sous-sous-catégorie si elle existe
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
        // Créer une clé unique pour le cache
        $cacheKey = $parentId . '|' . $nameEn;

        if (isset($this->categoryCache[$cacheKey])) {
            return $this->categoryCache[$cacheKey];
        }

        // Chercher si la catégorie existe déjà
        $category = Category::whereHas('translations', function ($query) use ($nameEn) {
            $query->where('locale', 'en')
                  ->where('name', $nameEn);
        })->where('parent_id', $parentId)->first();

        if (!$category) {
            // Créer la catégorie
            $category = Category::create([
                'parent_id' => $parentId,
            ]);

            // Créer la traduction en anglais
            CategoryTranslation::create([
                'category_id' => $category->id,
                'locale' => 'en',
                'name' => $nameEn,
            ]);

            // Créer la traduction en français si demandé
            if ($autoTranslate) {
                $nameFr = $this->translateToFrench($nameEn);
                
                CategoryTranslation::create([
                    'category_id' => $category->id,
                    'locale' => 'fr',
                    'name' => $nameFr,
                ]);
            }
        }

        // Mettre en cache
        $this->categoryCache[$cacheKey] = $category;

        return $category;
    }

    private function translateToFrench(string $text): string
    {
        // Vérifier le cache de traduction
        if (isset($this->translationCache[$text])) {
            return $this->translationCache[$text];
        }

        try {
            // Utiliser MyMemory Translation API (gratuit)
            $response = Http::timeout(5)
                ->get('https://api.mymemory.translated.net/get', [
                    'q' => $text,
                    'langpair' => 'en|fr'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['responseData']['translatedText'])) {
                    $translated = $data['responseData']['translatedText'];
                    
                    // Mettre en cache
                    $this->translationCache[$text] = $translated;
                    
                    // Petit délai pour éviter le rate limiting
                    usleep(200000); // 0.2 seconde
                    
                    return $translated;
                }
            }
        } catch (\Exception $e) {
            // En cas d'erreur, log mais continue
            $this->warn("Translation failed for: {$text}");
        }

        // Fallback : retourner le texte original
        $this->translationCache[$text] = $text;
        return $text;
    }
}