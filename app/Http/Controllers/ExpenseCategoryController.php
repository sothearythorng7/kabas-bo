<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Store $site)
    {
        $categories = ExpenseCategory::latest()->paginate(20);
        return view('expenses.categories.index', compact('site', 'categories'));
    }

    public function create(Store $site)
    {
        return view('expenses.categories.create', compact('site'));
    }

    public function store(Request $request, Store $site)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        ExpenseCategory::create($data);

        return redirect()->route('stores.expense-categories.index', $site)->with('success', 'Catégorie ajoutée.');
    }

    public function edit(Store $site, ExpenseCategory $category)
    {
        return view('expenses.categories.edit', compact('site', 'category'));
    }

    public function update(Request $request, Store $site, ExpenseCategory $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update($data);

        return redirect()->route('stores.expense-categories.index', $site)->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Store $site, ExpenseCategory $category)
    {
        $category->delete();
        return redirect()->route('stores.expense-categories.index', $site)->with('success', 'Catégorie supprimée.');
    }
}
