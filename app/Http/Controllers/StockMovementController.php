<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\StockBatch;
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
        $products = Product::with('stores', 'stockBatches')->get();
        $stores = Store::all();

        // Calculer le stock réel par magasin pour chaque produit
        $products->map(function ($product) {
            $product->realStock = $product->stores->mapWithKeys(function ($store) use ($product) {
                $stock = $product->stockBatches()
                    ->where('store_id', $store->id)
                    ->sum('quantity');
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
            ->with('success', __('messages.stock_movement.saved_and_validated'));
    }

    public function receive(StockMovement $movement)
    {
        if (!in_array($movement->status, [StockMovement::STATUS_VALIDATED, StockMovement::STATUS_IN_TRANSIT])) {
            return redirect()->back()->withErrors('Ce mouvement ne peut pas être réceptionné.');
        }

        DB::transaction(function () use ($movement) {
            $toStore = Store::find($movement->to_store_id);
            if (!$toStore) throw new \Exception("Magasin de destination introuvable.");

            foreach ($movement->items as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;

                // Créer un StockBatch pour le magasin de destination
                StockBatch::create([
                    'product_id' => $product->id,
                    'store_id'   => $toStore->id,
                    'quantity'   => $item->quantity,
                    'label'      => 'Transfert stock',
                ]);
            }

            $movement->update(['status' => StockMovement::STATUS_RECEIVED]);
        });

        return redirect()->route('stock-movements.index')
            ->with('success', __('messages.stock_movement.received_and_updated'));
    }

    public function cancel(StockMovement $movement)
    {
        if ($movement->status === StockMovement::STATUS_RECEIVED) {
            return redirect()->back()->withErrors('Impossible d’annuler un mouvement déjà réceptionné.');
        }

        DB::transaction(function () use ($movement) {
            $fromStore = $movement->fromStore;

            foreach ($movement->items as $item) {
                $product = Product::find($item->product_id);
                if (!$product || !$fromStore) continue;

                // Restaurer le stock dans le magasin source
                StockBatch::create([
                    'product_id' => $product->id,
                    'store_id'   => $fromStore->id,
                    'quantity'   => $item->quantity,
                    'label'      => 'Annulation mouvement',
                ]);
            }

            $movement->update(['status' => StockMovement::STATUS_CANCELLED]);
        });

        return redirect()->route('stock-movements.index')
            ->with('success', __('messages.stock_movement.cancelled_and_restored'));
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
