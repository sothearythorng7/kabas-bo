<?php

namespace App\Http\Controllers;

use App\Models\InvoiceCategory;
use Illuminate\Http\Request;

class InvoiceCategoryController extends Controller
{
    public function index()
    {
        $categories = InvoiceCategory::withCount('generalInvoices')->orderBy('name')->paginate(20);
        return view('invoice-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('invoice-categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:invoice_categories',
            'color' => 'required|string|max:7',
        ]);

        InvoiceCategory::create($request->only(['name', 'color']));

        return redirect()->route('invoice-categories.index', $request->only('store_id'))
            ->with('success', __('messages.invoice_category_created'));
    }

    public function edit(InvoiceCategory $invoiceCategory)
    {
        return view('invoice-categories.edit', compact('invoiceCategory'));
    }

    public function update(Request $request, InvoiceCategory $invoiceCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:invoice_categories,name,' . $invoiceCategory->id,
            'color' => 'required|string|max:7',
        ]);

        $invoiceCategory->update($request->only(['name', 'color']));

        return redirect()->route('invoice-categories.index', $request->only('store_id'))
            ->with('success', __('messages.invoice_category_updated'));
    }

    public function destroy(InvoiceCategory $invoiceCategory)
    {
        $invoiceCategory->delete();

        return redirect()->route('invoice-categories.index', request()->only('store_id'))
            ->with('success', __('messages.invoice_category_deleted'));
    }
}
