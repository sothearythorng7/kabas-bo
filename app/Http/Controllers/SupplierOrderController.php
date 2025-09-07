<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SupplierOrderController extends Controller
{
    public function index(Supplier $supplier)
    {
        $orders = $supplier->supplierOrders()->latest()->paginate(10);
        return view('supplier.orders.index', compact('supplier', 'orders'));
    }

    public function create(Supplier $supplier)
    {
        $products = $supplier->products()->with('brand')->get();
        $stores = Store::all(); // Récupérer tous les magasins (ou utiliser un scope si nécessaire)
        return view('supplier_orders.create', compact('supplier', 'products', 'stores'));
    }

    public function store(Request $request, Supplier $supplier)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.quantity' => 'nullable|integer|min:0',
            'destination_store_id' => 'required|exists:stores,id', // Validation pour destination_store_id
        ]);

        // Créer la commande
        $order = $supplier->supplierOrders()->create([
            'status' => 'pending',
            'destination_store_id' => $request->destination_store_id,
        ]);

        $syncData = [];

        // Charger tous les produits du fournisseur avec pivot
        $supplierProducts = $supplier->products()->get()->keyBy('id');

        foreach ($request->input('products') as $productId => $productData) {
            $quantity = (int) ($productData['quantity'] ?? 0);
            if ($quantity <= 0) continue;

            if (!isset($supplierProducts[$productId])) continue;

            $product = $supplierProducts[$productId];

            $syncData[$productId] = [
                'quantity_ordered' => $quantity,
                'purchase_price' => $product->pivot->purchase_price ?? 0,
                'sale_price' => $product->price,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Synchroniser les produits avec la commande
        $order->products()->sync($syncData);

        return redirect()->route('suppliers.edit', $supplier)->with('success', 'Commande créée avec succès.');
    }

    public function show(Supplier $supplier, SupplierOrder $order)
    {
        return view('supplier_orders.show', compact('supplier', 'order'));
    }

    public function edit(Supplier $supplier, SupplierOrder $order)
    {
        $products = $supplier->products()->with('brand')->get();
        $stores = Store::all(); // Récupérer tous les magasins
        return view('supplier_orders.edit', compact('supplier', 'order', 'products', 'stores'));
    }

    public function update(Request $request, Supplier $supplier, SupplierOrder $order)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.quantity' => 'nullable|integer|min:0',
            'destination_store_id' => 'required|exists:stores,id', // Validation pour destination_store_id
        ]);

        // Mettre à jour destination_store_id
        $order->update(['destination_store_id' => $request->destination_store_id]);

        $order->products()->detach();

        foreach ($request->products as $productId => $productData) {
            $quantity = (int) ($productData['quantity'] ?? 0);
            if ($quantity <= 0) continue;

            $product = Product::findOrFail($productId);

            // Récupérer le prix d'achat depuis le fournisseur
            $purchasePrice = $supplier->products()->where('product_id', $productId)->first()?->pivot->purchase_price ?? 0;

            $order->products()->attach($productId, [
                'purchase_price'   => $purchasePrice,
                'sale_price'       => $product->price,
                'quantity_ordered' => $quantity,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        return redirect()->route('suppliers.edit', $supplier)->with('success', 'Commande mise à jour.');
    }

    public function destroy(Supplier $supplier, SupplierOrder $order)
    {
        $order->delete();
        return redirect()->route('suppliers.edit', $supplier)->with('success', 'Commande supprimée.');
    }

    public function validateOrder(Supplier $supplier, SupplierOrder $order)
    {
        $order->update(['status' => 'waiting_reception']);
        return back()->with('success', 'Commande validée et en attente de réception.');
    }

    public function generatePdf(Supplier $supplier, SupplierOrder $order)
    {
        $pdf = Pdf::loadView('supplier_orders.pdf', compact('supplier', 'order'));
        return $pdf->download("commande_{$order->id}.pdf");
    }

    public function receptionForm(Supplier $supplier, SupplierOrder $order)
    {
        // Charger les produits liés à la commande
        $order->load('products');
        return view('supplier_orders.reception', compact('supplier', 'order'));
    }

    public function storeReception(Request $request, Supplier $supplier, SupplierOrder $order)
    {
        foreach ($request->input('products', []) as $productId => $qtyReceived) {
            $qtyReceived = (int) $qtyReceived;

            // Mettre à jour la quantité reçue dans le pivot
            $order->products()->updateExistingPivot($productId, [
                'quantity_received' => $qtyReceived,
            ]);

            // Ajouter la quantité reçue au stock du magasin de destination
            $store = Store::find($order->destination_store_id);
            if ($store) {
                $currentStock = $store->products()->where('product_id', $productId)->first()?->pivot->stock_quantity ?? 0;

                $store->products()->syncWithoutDetaching([
                    $productId => ['stock_quantity' => $currentStock + $qtyReceived]
                ]);
            }
        }

        // Changer le statut de la commande
        $order->update(['status' => 'received']);

        return redirect()->route('suppliers.edit', $supplier)->with('success', 'Commande réceptionnée et stock mis à jour.');
    }
}