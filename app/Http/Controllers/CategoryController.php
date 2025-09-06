<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

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
                $category->translations()->create([
                    'locale' => $locale,
                    'name' => $name,
                ]);
            }
        }

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

        $category->update([
            'parent_id' => $request->parent_id,
        ]);

        $names = $request->input('name', []);
        foreach ($names as $locale => $name) {
            if ($name) {
                $category->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['name' => $name]
                );
            } else {
                $category->translations()->where('locale', $locale)->delete();
            }
        }

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
}
