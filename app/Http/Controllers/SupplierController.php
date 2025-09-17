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

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'pending':
                case 'waiting_reception':
                case 'waiting_invoice':
                    $query->where('status', $request->status);
                    break;
                case 'received_unpaid':
                    $query->where('status', 'received')->where('is_paid', false);
                    break;
                case 'received_paid':
                    $query->where('status', 'received')->where('is_paid', true);
                    break;
            }
        }

        $orders = $query->paginate(10)->appends($request->only('status'));

        // Montant total des factures non payées et nombre de commandes dans ce statut
        $unpaidOrdersQuery = $supplier->supplierOrders()
            ->where('status', 'received')
            ->where('is_paid', false)
            ->with('products');

        $totalUnpaidAmount = $unpaidOrdersQuery->get()->sum(fn($order) =>
            $order->products->sum(fn($p) => ($p->pivot->price_invoiced ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_ordered ?? 0))
        );

        $unpaidOrdersCount = $unpaidOrdersQuery->count();

        return view('suppliers.edit', compact(
            'supplier',
            'products',
            'orders',
            'totalUnpaidAmount',
            'unpaidOrdersCount'
        ));
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
