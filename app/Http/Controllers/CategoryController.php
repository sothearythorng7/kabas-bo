<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\CategorySlugHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();
        $allCategories = Category::with('translations')->get();

        return view('categories.index', compact('categories', 'allCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|array',
            'name.*' => 'nullable|string|max:255',
        ]);

        $category = Category::create([
            'parent_id' => $request->parent_id,
        ]);

        $names = $request->input('name', []);
        foreach ($names as $locale => $name) {
            if ($name) {
                $fullSlug = $this->generateUniqueFullSlug($category, $locale, $name);

                $category->translations()->create([
                    'locale' => $locale,
                    'name' => $name,
                    'full_slug' => $fullSlug,
                ]);
            }
        }

        $this->clearFrontCache();

        return redirect()->route('categories.index')->with('success', __('messages.category.saved'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|array',
            'name.*' => 'nullable|string|max:255',
        ]);

        if ($request->parent_id == $category->id) {
            return redirect()->back()->withErrors(__('messages.category.invalid_parent'));
        }

        $category->update(['parent_id' => $request->parent_id]);

        $names = $request->input('name', []);
        foreach ($names as $locale => $name) {
            if ($name) {
                // Récupérer l'ancienne URL pour redirection
                $oldFullSlug = $category->translation($locale)?->full_slug;

                $fullSlug = $this->generateUniqueFullSlug($category, $locale, $name);

                $category->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['name' => $name, 'full_slug' => $fullSlug]
                );

                // Stocker l'ancienne URL pour redirection
                if ($oldFullSlug && $oldFullSlug !== $fullSlug) {
                    CategorySlugHistory::create([
                        'category_id' => $category->id,
                        'locale' => $locale,
                        'old_full_slug' => $oldFullSlug,
                        'new_full_slug' => $fullSlug,
                    ]);
                }

                // Recalculer le full_slug pour les enfants
                $this->updateChildrenFullSlugs($category, $locale);
            } else {
                $category->translations()->where('locale', $locale)->delete();
            }
        }

        $this->clearFrontCache();

        return redirect()->route('categories.index')->with('success', __('messages.category.saved'));
    }

    public function destroy(Category $category)
    {
        $category->children()->each(function ($child) {
            $child->delete();
        });

        $category->translations()->delete();
        $category->delete();

        return redirect()->route('categories.index')->with('success', __('messages.category.deleted'));
    }

    protected function generateUniqueFullSlug(Category $category, string $locale, string $name): string
    {
        $slug = Str::slug($name);
        $fullSlug = $this->computeFullSlug($category, $locale, $slug);
        $counter = 1;

        $existingFullSlugs = CategoryTranslation::pluck('full_slug')->toArray();

        while (in_array($fullSlug, $existingFullSlugs)) {
            $fullSlug = $this->computeFullSlug($category, $locale, $slug . '-' . $counter++);
        }

        return $fullSlug;
    }

    protected function computeFullSlug(Category $category, string $locale, string $slug): string
    {
        $parts = [$slug];
        $parent = $category->parent;

        while ($parent) {
            $parentTranslation = $parent->translations()->where('locale', $locale)->first();
            if ($parentTranslation) {
                array_unshift($parts, Str::slug($parentTranslation->name));
            }
            $parent = $parent->parent;
        }

        array_unshift($parts, $locale); // Ajouter la langue au début
        return implode('/', $parts);
    }

    protected function updateChildrenFullSlugs(Category $category, string $locale)
    {
        foreach ($category->children as $child) {
            $translation = $child->translation($locale);
            if ($translation) {
                $oldFullSlug = $translation->full_slug;
                $newFullSlug = $this->computeFullSlug($child, $locale, Str::slug($translation->name));

                if ($oldFullSlug !== $newFullSlug) {
                    $translation->update(['full_slug' => $newFullSlug]);

                    CategorySlugHistory::create([
                        'category_id' => $child->id,
                        'locale' => $locale,
                        'old_full_slug' => $oldFullSlug,
                        'new_full_slug' => $newFullSlug,
                    ]);

                    // Recurse pour les enfants des enfants
                    $this->updateChildrenFullSlugs($child, $locale);
                }
            }
        }
    }

    protected function clearFrontCache(): void
    {
        // Vider le cache du front
        $frontPath = '/var/www/kabas-site';
        $processFront = new Process(['php', 'artisan', 'cache:clear']);
        $processFront->setWorkingDirectory($frontPath);
        $processFront->run();

        if (!$processFront->isSuccessful()) {
            \Log::error('Erreur lors du vidage du cache front: ' . $processFront->getErrorOutput());
        }

        // Vider le cache du BO
        $boPath = base_path(); // chemin vers le BO actuel
        $processBo = new Process(['php', 'artisan', 'cache:clear']);
        $processBo->setWorkingDirectory($boPath);
        $processBo->run();

        if (!$processBo->isSuccessful()) {
            \Log::error('Erreur lors du vidage du cache BO: ' . $processBo->getErrorOutput());
        }
    }
}
