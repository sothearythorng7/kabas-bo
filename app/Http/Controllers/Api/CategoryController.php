<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());

        $categories = cache()->remember('api_categories_'.$locale, 3600, function () use ($locale) {
            return Category::whereNull('parent_id')
                ->with(['children', 'translations' => fn($q) => $q->where('locale', $locale)])
                ->get()
                ->map(fn($cat) => $this->formatCategory($cat, $locale));
        });

        // Plus besoin de re-map, le cache contient déjà le format JSON
        return response()->json($categories);
    }

    private function formatCategory(Category $category, $locale)
    {
        $translation = $category->translation($locale);

        $redirects = $category->slugHistories()
            ->where('locale', $locale)
            ->get(['old_full_slug', 'new_full_slug'])
            ->map(fn($h) => [
                'from' => $h->old_full_slug,
                'to' => $h->new_full_slug,
            ]);

        return [
            'id' => $category->id,
            'name' => $translation?->name ?? '—',
            'full_slug' => $translation?->full_slug ?? '#',
            'redirects' => $redirects,
            'children' => $category->children->map(fn($child) => $this->formatCategory($child, $locale)),
        ];
    }
}
