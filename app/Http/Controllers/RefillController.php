<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Refill;
use App\Models\Product;
use App\Models\Store;
use App\Models\StockBatch;
use Illuminate\Http\Request;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;

class RefillController extends Controller
{
    public function index(Supplier $supplier)
    {
        $refills = $supplier->refills()->latest()->paginate(10);
        return view('refills.index', compact('supplier', 'refills'));
    }

    public function show(Supplier $supplier, Refill $refill)
    {
        $refill->load('products.brand');
        return view('refills.show', compact('supplier', 'refill'));
    }

    public function receptionForm(Supplier $supplier)
    {
        $products = $supplier->products()->with('brand')->get();
        $stores = Store::all();
        return view('refills.reception', compact('supplier', 'products', 'stores'));
    }

    public function storeReception(Request $request, Supplier $supplier)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.quantity' => 'nullable|integer|min:0',
            'destination_store_id' => 'required|exists:stores,id',
        ]);

        DB::transaction(function() use ($request, $supplier) {

            $refill = $supplier->refills()->create([
                'destination_store_id' => $request->destination_store_id,
            ]);

            $syncData = [];
            $supplierProducts = $supplier->products()->get()->keyBy('id');
            $store = Store::find($request->destination_store_id);

            foreach ($request->input('products') as $productId => $productData) {
                $quantity = (int) ($productData['quantity'] ?? 0);
                if ($quantity <= 0) continue;
                if (!isset($supplierProducts[$productId])) continue;

                $product = $supplierProducts[$productId];
                $purchasePrice = $product->pivot->purchase_price ?? 0;

                $syncData[$productId] = [
                    'quantity_received' => $quantity,
                    'purchase_price'    => $purchasePrice,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];

                // Création d’un lot dans le stock
                $batch = StockBatch::create([
                    'product_id'      => $productId,
                    'store_id'        => $store->id,
                    'reseller_id'     => null,
                    'quantity'        => $quantity,
                    'unit_price'      => $purchasePrice,
                    'source_refill_id'=> $refill->id,
                    'label'           => 'Réception refill fournisseur',
                ]);

                // Création de la transaction de stock
                StockTransaction::create([
                    'stock_batch_id' => $batch->id,
                    'store_id'       => $store->id,
                    'product_id'     => $productId,
                    'type'           => 'in',
                    'quantity'       => $quantity,
                    'reason'         => 'supplier_refill',
                    'user_id'        => auth()->id(),
                ]);
            }

            $refill->products()->sync($syncData);
        });

        return redirect()->route('suppliers.edit', $supplier)
            ->with('success', 'Refill réceptionné avec succès.');
    }
}
