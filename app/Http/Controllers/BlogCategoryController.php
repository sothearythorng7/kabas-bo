<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::withCount('posts')
                                  ->orderBy('sort_order')
                                  ->orderBy('created_at', 'desc')
                                  ->paginate(20);

        return view('blog.categories.index', compact('categories'));
    }

    public function create()
    {
        $locales = config('app.website_locales', ['fr', 'en']);
        return view('blog.categories.create', compact('locales'));
    }

    public function store(Request $request)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = new BlogCategory();
        $category->is_active = $request->boolean('is_active', true);
        $category->sort_order = $data['sort_order'] ?? 0;

        // Translations
        foreach ($locales as $locale) {
            $category->setTranslation('name', $locale, $data['name'][$locale] ?? '');
            $category->setTranslation('slug', $locale, Str::slug($data['name'][$locale] ?? ''));
            $category->setTranslation('description', $locale, $data['description'][$locale] ?? '');
        }

        $category->save();

        return redirect()->route('blog.categories.index')
                        ->with('success', 'Catégorie créée avec succès');
    }

    public function edit(BlogCategory $category)
    {
        $locales = config('app.website_locales', ['fr', 'en']);
        return view('blog.categories.edit', compact('category', 'locales'));
    }

    public function update(Request $request, BlogCategory $category)
    {
        $locales = config('app.website_locales', ['fr', 'en']);

        $data = $request->validate([
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category->is_active = $request->boolean('is_active', true);
        $category->sort_order = $data['sort_order'] ?? 0;

        // Translations
        foreach ($locales as $locale) {
            $category->setTranslation('name', $locale, $data['name'][$locale] ?? '');
            $category->setTranslation('slug', $locale, Str::slug($data['name'][$locale] ?? ''));
            $category->setTranslation('description', $locale, $data['description'][$locale] ?? '');
        }

        $category->save();

        return redirect()->route('blog.categories.index')
                        ->with('success', 'Catégorie mise à jour avec succès');
    }

    public function destroy(BlogCategory $category)
    {
        // Check if category has posts
        if ($category->posts()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer une catégorie contenant des articles');
        }

        $category->delete();

        return redirect()->route('blog.categories.index')
                        ->with('success', 'Catégorie supprimée avec succès');
    }
}
