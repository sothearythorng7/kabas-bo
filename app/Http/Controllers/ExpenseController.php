<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Store $site)
    {
        $expenses = $site->expenses()->latest()->paginate(20);
        return view('expenses.index', compact('site', 'expenses'));
    }

    public function create(Store $site)
    {
        $categories = ExpenseCategory::all();
        return view('expenses.create', compact('site', 'categories'));
    }

    public function store(Request $request, Store $site)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric',
            'document' => 'nullable|file|mimes:pdf,jpg,png',
        ]);

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')->store('expenses');
        }

        $site->expenses()->create($data);

        return redirect()->route('stores.expenses.index', $site)->with('success', __('messages.expense.created'));
    }

    public function edit(Store $site, Expense $expense)
    {
        $categories = ExpenseCategory::all();
        return view('expenses.edit', compact('site', 'expense', 'categories'));
    }

    public function update(Request $request, Store $site, Expense $expense)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric',
            'document' => 'nullable|file|mimes:pdf,jpg,png',
        ]);

        if ($request->hasFile('document')) {
            if ($expense->document) {
                Storage::delete($expense->document);
            }
            $data['document'] = $request->file('document')->store('expenses');
        }

        $expense->update($data);

        return redirect()->route('stores.expenses.index', $site)->with('success', __('messages.expense.updated'));
    }

    public function destroy(Store $site, Expense $expense)
    {
        if ($expense->document) {
            Storage::delete($expense->document);
        }

        $expense->delete();

        return redirect()->route('stores.expenses.index', $site)->with('success', __('messages.expense.deleted'));
    }
}
