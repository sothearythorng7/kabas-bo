<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Reseller;
use App\Models\ResellerStockReturn;
use App\Models\Store;
use App\Models\StockBatch;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResellerStockReturnController extends Controller
{
    /**
     * Affiche le formulaire de création d'un retour
     */
    public function create($resellerId)
    {
        // Cas shop
        if (str_starts_with($resellerId, 'shop-')) {
            $shopId = (int) str_replace('shop-', '', $resellerId);
            $shop = Store::findOrFail($shopId);

            $reseller = (object)[
                'id' => $resellerId,
                'name' => $shop->name,
                'type' => 'consignment',
                'is_shop' => true,
                'store' => $shop,
            ];

            $stock = $shop->getCurrentStock();
        } else {
            // Reseller classique
            $reseller = Reseller::findOrFail($resellerId);
            $stock = $reseller->getCurrentStock();
        }

        // Récupérer les produits en stock chez ce reseller
        $products = Product::whereIn('id', $stock->keys())
            ->where(function($q) use ($stock) {
                // Seulement les produits avec du stock > 0
                $q->whereIn('id', $stock->filter(fn($qty) => $qty > 0)->keys());
            })
            ->with('brand')
            ->orderBy('name')
            ->get();

        // Destinations possibles (warehouses et shops)
        $destinations = Store::orderBy('type')->orderBy('name')->get();

        return view('resellers.returns.create', compact('reseller', 'products', 'stock', 'destinations'));
    }

    /**
     * Enregistre un nouveau retour
     */
    public function store(Request $request, $resellerId)
    {
        $request->validate([
            'destination_store_id' => 'required|exists:stores,id',
            'note' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.reason' => 'nullable|string',
        ]);

        // Filtrer les items avec quantité > 0
        $itemsWithQuantity = collect($request->items)->filter(fn($item) => (int)$item['quantity'] > 0);

        if ($itemsWithQuantity->isEmpty()) {
            return back()->withErrors(['items' => __('messages.resellers.at_least_one_item')])->withInput();
        }

        // Déterminer si c'est un shop ou un reseller
        $isShop = str_starts_with($resellerId, 'shop-');
        $shopId = null;
        $realResellerId = null;

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $resellerId);
            $shop = Store::findOrFail($shopId);
            $stock = $shop->getCurrentStock();
        } else {
            $realResellerId = $resellerId;
            $reseller = Reseller::findOrFail($resellerId);
            $stock = $reseller->getCurrentStock();
        }

        // Vérifier que les quantités demandées sont disponibles
        foreach ($itemsWithQuantity as $item) {
            $available = $stock[$item['product_id']] ?? 0;
            if ($item['quantity'] > $available) {
                $product = Product::find($item['product_id']);
                $productName = $product->name[app()->getLocale()] ?? reset($product->name);
                return back()->withErrors([
                    'items' => "Quantité insuffisante pour {$productName}. Disponible: {$available}"
                ])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Créer le retour
            $return = ResellerStockReturn::create([
                'reseller_id' => $realResellerId,
                'store_id' => $shopId,
                'destination_store_id' => $request->destination_store_id,
                'user_id' => auth()->id(),
                'status' => ResellerStockReturn::STATUS_DRAFT,
                'note' => $request->note,
            ]);

            // Créer les items (seulement ceux avec quantité > 0)
            foreach ($itemsWithQuantity as $item) {
                $return->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('resellers.returns.show', [$resellerId, $return->id])
                ->with('success', __('messages.resellers.return_created'));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Affiche un retour
     */
    public function show($resellerId, $returnId)
    {
        $return = ResellerStockReturn::with(['items.product.brand', 'destinationStore', 'user'])
            ->findOrFail($returnId);

        // Récupérer le reseller
        if (str_starts_with($resellerId, 'shop-')) {
            $shopId = (int) str_replace('shop-', '', $resellerId);
            $shop = Store::findOrFail($shopId);
            $reseller = (object)[
                'id' => $resellerId,
                'name' => $shop->name,
                'type' => 'consignment',
                'is_shop' => true,
            ];
        } else {
            $reseller = Reseller::findOrFail($resellerId);
        }

        return view('resellers.returns.show', compact('reseller', 'return'));
    }

    /**
     * Valide un retour (effectue le transfert de stock)
     */
    public function validateReturn(Request $request, $resellerId, $returnId)
    {
        $return = ResellerStockReturn::with('items')->findOrFail($returnId);

        if ($return->status !== ResellerStockReturn::STATUS_DRAFT) {
            return back()->withErrors(['error' => 'Ce retour a déjà été traité.']);
        }

        // Déterminer si c'est un shop ou un reseller à partir des données du retour
        $isShop = !empty($return->store_id);

        DB::beginTransaction();
        try {
            foreach ($return->items as $item) {
                // 1. Déduire du stock du reseller/shop
                if ($isShop) {
                    $this->deductFromShopStock($return->store_id, $item->product_id, $item->quantity);
                } else {
                    $this->deductFromResellerStock($return->reseller_id, $item->product_id, $item->quantity, $return->destination_store_id);
                }

                // 2. Ajouter au stock de destination
                $this->addToDestinationStock($return->destination_store_id, $item->product_id, $item->quantity);
            }

            // Mettre à jour le statut
            $return->update([
                'status' => ResellerStockReturn::STATUS_VALIDATED,
                'validated_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('resellers.show', $resellerId)
                ->with('success', __('messages.resellers.return_validated'));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Annule un retour
     */
    public function cancel($resellerId, $returnId)
    {
        $return = ResellerStockReturn::findOrFail($returnId);

        if ($return->status !== ResellerStockReturn::STATUS_DRAFT) {
            return back()->withErrors(['error' => 'Seuls les retours en brouillon peuvent être annulés.']);
        }

        $return->update(['status' => ResellerStockReturn::STATUS_CANCELLED]);

        return redirect()
            ->route('resellers.show', $resellerId)
            ->with('success', __('messages.resellers.return_cancelled'));
    }

    /**
     * Déduit du stock d'un shop
     */
    private function deductFromShopStock($storeId, $productId, $quantity)
    {
        $batches = StockBatch::where('store_id', $storeId)
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc') // FIFO
            ->get();

        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $toDeduct = min($batch->quantity, $remaining);
            $batch->decrement('quantity', $toDeduct);

            // Log la transaction
            StockTransaction::create([
                'stock_batch_id' => $batch->id,
                'store_id' => $storeId,
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => $toDeduct,
                'reason' => 'reseller_return',
            ]);

            $remaining -= $toDeduct;
        }

        if ($remaining > 0) {
            throw new \Exception("Stock insuffisant pour le produit ID {$productId}");
        }
    }

    /**
     * Déduit du stock d'un reseller classique
     */
    private function deductFromResellerStock($resellerId, $productId, $quantity, $destinationStoreId)
    {
        $batches = StockBatch::where('reseller_id', $resellerId)
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc') // FIFO
            ->get();

        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $toDeduct = min($batch->quantity, $remaining);
            $batch->decrement('quantity', $toDeduct);

            // Log la transaction (store_id = destination car c'est un retour vers ce store)
            StockTransaction::create([
                'stock_batch_id' => $batch->id,
                'store_id' => $destinationStoreId,
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => $toDeduct,
                'reason' => 'reseller_return',
            ]);

            $remaining -= $toDeduct;
        }

        if ($remaining > 0) {
            throw new \Exception("Stock insuffisant pour le produit ID {$productId}");
        }
    }

    /**
     * Ajoute au stock de destination
     */
    private function addToDestinationStock($storeId, $productId, $quantity)
    {
        // Créer un nouveau batch pour le stock entrant
        $batch = StockBatch::create([
            'product_id' => $productId,
            'store_id' => $storeId,
            'quantity' => $quantity,
            'unit_price' => 0, // Prix non applicable pour un retour
        ]);

        // Log la transaction
        StockTransaction::create([
            'stock_batch_id' => $batch->id,
            'store_id' => $storeId,
            'product_id' => $productId,
            'type' => 'in',
            'quantity' => $quantity,
            'reason' => 'reseller_return_received',
        ]);
    }
}
