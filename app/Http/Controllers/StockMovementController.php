<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
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
        $products = Product::all();
        $shops = Store::all();

        return view('stock_movements.create', compact('products', 'shops'));
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $movement = StockMovement::create([
                'from_store_id' => $request->from_store_id,
                'to_store_id'   => $request->to_store_id,
                'note'          => $request->note,
                'user_id'       => auth()->id(),
                'status'        => StockMovement::STATUS_VALIDATED, // ou draft si tu veux valider après
            ]);

            foreach ($request->products ?? [] as $productId => $qty) {
                if ($qty > 0) {
                    $movement->items()->create([
                        'product_id' => $productId,
                        'quantity'   => $qty,
                    ]);

                    // Décrémenter le stock du magasin source immédiatement
                    if ($request->from_store_id) {
                        DB::table('product_store')
                            ->where('product_id', $productId)
                            ->where('store_id', $request->from_store_id)
                            ->decrement('stock_quantity', $qty);
                    }
                }
            }
        });

        return redirect()->route('stock-movements.index')
            ->with('success', 'Mouvement enregistré et validé.');
    }

    public function receive(StockMovement $movement)
    {
        if ($movement->status !== StockMovement::STATUS_VALIDATED &&
            $movement->status !== StockMovement::STATUS_IN_TRANSIT) {
            return redirect()->back()->withErrors('Ce mouvement ne peut pas être réceptionné.');
        }

        DB::transaction(function () use ($movement) {
            foreach ($movement->items as $item) {
                DB::table('product_store')->updateOrInsert(
                    ['product_id' => $item->product_id, 'store_id' => $movement->to_store_id],
                    ['stock_quantity' => DB::raw('stock_quantity + '.$item->quantity)]
                );
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
            // Restaurer le stock source uniquement si on avait décrémenté
            if ($movement->from_store_id) {
                foreach ($movement->items as $item) {
                    DB::table('product_store')
                        ->where('product_id', $item->product_id)
                        ->where('store_id', $movement->from_store_id)
                        ->increment('stock_quantity', $item->quantity);
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
