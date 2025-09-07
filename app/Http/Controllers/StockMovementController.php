<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\StockLot;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class StockMovementController extends Controller
{
    public function index()
    {
        $movements = StockMovement::with(['fromStore', 'toStore', 'user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('stock_movements.index', compact('movements'));
    }

    public function create()
    {
        $products = Product::with('stores', 'stockLots')->get();
        $stores = Store::all();

        // Calculer le stock réel par magasin pour chaque produit
        $products->map(function ($product) {
            $product->realStock = $product->stores->mapWithKeys(function ($store) use ($product) {
                $stock = $product->stockLots()
                    ->where('store_id', $store->id)
                    ->sum('quantity_remaining');
                return [$store->id => $stock];
            });
            return $product;
        });

        return view('stock_movements.create', compact('products', 'stores'));
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $movement = StockMovement::create([
                'type'          => StockMovement::TYPE_TRANSFER,
                'from_store_id' => $request->from_store_id,
                'to_store_id'   => $request->to_store_id,
                'note'          => $request->note,
                'user_id'       => auth()->id(),
                'status'        => StockMovement::STATUS_VALIDATED,
            ]);

            $fromStore = Store::find($request->from_store_id);

            foreach ($request->products ?? [] as $productId => $qty) {
                if ($qty <= 0) continue;

                $movement->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $qty,
                ]);

                if ($fromStore) {
                    $product = Product::find($productId);
                    if ($product && !$product->removeStock($fromStore, $qty)) {
                        throw new \Exception("Stock insuffisant pour le produit {$product->name}");
                    }
                }
            }
        });

        return redirect()->route('stock-movements.index')
            ->with('success', 'Mouvement enregistré et validé.');
    }

    public function receive(StockMovement $movement)
    {
        if (!in_array($movement->status, [StockMovement::STATUS_VALIDATED, StockMovement::STATUS_IN_TRANSIT])) {
            return redirect()->back()->withErrors('Ce mouvement ne peut pas être réceptionné.');
        }

        DB::transaction(function () use ($movement) {
            if (!in_array($movement->type, [StockMovement::TYPE_TRANSFER, StockMovement::TYPE_IN])) return;

            $toStore = Store::find($movement->to_store_id);
            if (!$toStore) throw new \Exception("Magasin de destination introuvable.");

            foreach ($movement->items as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;

                // Créer un StockLot pour le magasin de destination
                StockLot::create([
                    'product_id'         => $product->id,
                    'store_id'           => $toStore->id,
                    'supplier_id'        => null,
                    'supplier_order_id'  => null,
                    'purchase_price'     => $product->price,
                    'quantity'           => $item->quantity,
                    'quantity_remaining' => $item->quantity,
                    'batch_number'       => null,
                    'expiry_date'        => null,
                ]);

                // Mettre à jour le stock global pivot
                $currentStock = $toStore->products()->where('product_id', $product->id)->first()?->pivot->stock_quantity ?? 0;
                $toStore->products()->syncWithoutDetaching([
                    $product->id => ['stock_quantity' => $currentStock + $item->quantity]
                ]);
            }

            $movement->update(['status' => StockMovement::STATUS_RECEIVED]);
        });

        return redirect()->route('stock-movements.index')
            ->with('success', 'Mouvement réceptionné et stock mis à jour.');
    }

    public function cancel(StockMovement $movement)
    {
        if ($movement->status === StockMovement::STATUS_RECEIVED) {
            return redirect()->back()->withErrors('Impossible d’annuler un mouvement déjà réceptionné.');
        }

        DB::transaction(function () use ($movement) {
            if ($movement->from_store_id) {
                $fromStore = Store::find($movement->from_store_id);

                foreach ($movement->items as $item) {
                    $product = Product::find($item->product_id);
                    if (!$product) continue;

                    // Restaurer les lots retirés
                    StockLot::create([
                        'product_id'         => $product->id,
                        'store_id'           => $fromStore->id,
                        'supplier_id'        => null,
                        'supplier_order_id'  => null,
                        'purchase_price'     => $product->price,
                        'quantity'           => $item->quantity,
                        'quantity_remaining' => $item->quantity,
                    ]);

                    // Mise à jour du pivot product_store
                    $currentStock = $fromStore->products()->where('product_id', $product->id)->first()?->pivot->stock_quantity ?? 0;
                    $fromStore->products()->syncWithoutDetaching([
                        $product->id => ['stock_quantity' => $currentStock + $item->quantity]
                    ]);
                }
            }

            $movement->update(['status' => StockMovement::STATUS_CANCELLED]);
        });

        return redirect()->route('stock-movements.index')
            ->with('success', 'Mouvement annulé et stock restauré.');
    }

    public function show(StockMovement $movement)
    {
        $movement->load(['user', 'fromStore', 'toStore', 'items.product']);
        return view('stock_movements.show', compact('movement'));
    }

    public function pdf(StockMovement $movement)
    {
        $movement->load(['user', 'fromStore', 'toStore', 'items.product']);
        $pdf = Pdf::loadView('stock_movements.pdf', compact('movement'))
            ->setPaper('a4', 'portrait');
        $filename = 'stock_movement_'.$movement->id.'.pdf';
        return $pdf->download($filename);
    }
}
