<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Product;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::with('contacts')->paginate(20);
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'address'    => 'required|string',
            'last_name'  => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email'      => 'required|email',
            'phone'      => 'required|string|max:50',
        ]);

        $supplier = Supplier::create($request->only(['name', 'address']));

        $supplier->contacts()->create($request->only(['last_name','first_name','email','phone']));

        return redirect()->route('suppliers.index')->with('success', 'Supplier created');
    }

public function edit(Supplier $supplier, Request $request)
{
    // Charger les relations nécessaires pour la page
    $supplier->load(['contacts', 'products.stores', 'products.brand']);

    // Produits paginés
    $products = $supplier->products()->with(['stores', 'brand'])->paginate(10);

    // Commandes paginées avec filtrage par statut
    $query = $supplier->supplierOrders()->latest();

    if ($request->filled('status') && in_array($request->status, ['pending','waiting_reception','waiting_invoice'])) {
        $query->where('status', $request->status);
    }

    $orders = $query->paginate(10)->appends($request->only('status'));

    return view('suppliers.edit', compact('supplier', 'products', 'orders'));
}


    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        $supplier->update($request->only(['name','address']));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted');
    }

    public function updatePurchasePrice(Request $request, Supplier $supplier, Product $product)
    {
        $request->validate([
            'purchase_price' => 'required|numeric|min:0',
        ]);

        $supplier->products()->updateExistingPivot($product->id, [
            'purchase_price' => $request->purchase_price,
        ]);

        return back()->with('success', 'Purchase price updated');
    }
}
