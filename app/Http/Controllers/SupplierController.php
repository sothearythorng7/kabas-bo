<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\FinancialPaymentMethod;
use Illuminate\Support\Facades\View;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        // Exclure les fournisseurs de matières premières (ils sont gérés dans /factory/suppliers)
        $suppliers = Supplier::with('contacts')
            ->productSuppliers()
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->only('search'));
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
            'address'    => 'nullable|string',
            'type'       => 'required|in:buyer,consignment',
            'last_name'  => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email'      => 'required|email',
            'phone'      => 'required|string|max:50',
        ]);

        $supplier = Supplier::create($request->only(['name', 'address', 'type']));

        $supplier->contacts()->create($request->only(['last_name','first_name','email','phone']));

        return redirect()->route('suppliers.index')->with('success', 'Supplier created');
    }

public function edit(Supplier $supplier, Request $request)
{
    $supplier->load(['contacts', 'products.stores', 'products.brand', 'saleReports.store']);

    // Produits paginés avec nombre par page configurable
    $perPage = (int) $request->get('per_page', 50);
    $perPage = in_array($perPage, [50, 100, 200]) ? $perPage : 50;

    // Si recherche textuelle, utiliser Meilisearch via Scout
    if ($request->filled('product_search')) {
        $supplierProductIds = $supplier->products()->pluck('products.id')->toArray();

        $searchQuery = Product::search($request->product_search)
            ->query(function ($builder) use ($supplierProductIds) {
                $builder->whereIn('id', $supplierProductIds)
                    ->with(['stores', 'brand']);
            });

        $products = $searchQuery->paginate($perPage)->appends($request->only(['per_page', 'product_search']));
    } else {
        $products = $supplier->products()->with(['stores', 'brand'])->paginate($perPage)->appends($request->only('per_page'));
    }

    // Commandes
    $orders = collect();
    $totalUnpaidAmount = 0;
    $unpaidOrdersCount = 0;

    $paymentMethods = FinancialPaymentMethod::all();

    // Toutes les commandes, quel que soit le type
    $query = $supplier->supplierOrders()->latest();

    if ($request->filled('status')) {
        switch ($request->status) {
            case 'pending':
            case 'waiting_reception':
                $query->where('status', $request->status);
                break;
            case 'received_unpaid':
                if ($supplier->isBuyer()) {
                    $query->where('status', 'received')->where('is_paid', false);
                }
                break;
            case 'received_paid':
                if ($supplier->isBuyer()) {
                    $query->where('status', 'received')->where('is_paid', true);
                }
                break;
        }
    }

    $orders = $query->paginate(10)->appends($request->only('status'));

    // Montant total des factures non payées et nombre de commandes uniquement pour les buyers
    if ($supplier->isBuyer()) {
        $unpaidOrdersQuery = $supplier->supplierOrders()
            ->where('status', 'received')
            ->where('is_paid', false)
            ->with('products');

        $totalUnpaidAmount = $unpaidOrdersQuery->get()->sum(fn($order) =>
            $order->products->sum(fn($p) => ($p->pivot->invoice_price ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_received ?? 0))
        );

        $unpaidOrdersCount = $unpaidOrdersQuery->count();
    }

    return view('suppliers.edit', compact(
        'supplier',
        'products',
        'orders',
        'totalUnpaidAmount',
        'unpaidOrdersCount',
        'paymentMethods'
    ));
}



    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string',
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
