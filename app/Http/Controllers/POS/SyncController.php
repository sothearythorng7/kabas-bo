<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\FinancialPaymentMethod;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\StockBatch;
use App\Models\StockTransaction;

class SyncController extends Controller
{
    public function users()
    {
        return User::select('id', 'name', 'pin_code', 'store_id')
            ->whereNotNull('pin_code')
            ->whereNotNull('store_id')
            ->get();
    }

    public function sales(Request $request)
    {
        $data = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'sales' => 'required|array',
            'sales.*.id' => 'required',
            'sales.*.payment_type' => 'required|string',
            'sales.*.total' => 'required|numeric',
            'sales.*.discounts' => 'nullable|array',
            'sales.*.items' => 'required|array|min:1',
            'sales.*.items.*.product_id' => 'required|exists:products,id',
            'sales.*.items.*.quantity' => 'required|integer|min:1',
            'sales.*.items.*.price' => 'required|numeric',
            'sales.*.items.*.discounts' => 'nullable|array',
        ]);

        $shift = Shift::findOrFail($data['shift_id']);
        $storeId = $shift->store_id;
        $store = $shift->store;

        $userId = $shift->user_id;

        $financialAccount = \App\Models\FinancialAccount::where('code', '701')->first();
        if (!$financialAccount) {
            throw new \Exception("Le compte financier 701 est introuvable.");
        }

        $syncedSales = [];

        foreach ($data['sales'] as $saleData) {
            // 1️⃣ Création de la vente
            $sale = \App\Models\Sale::create([
                'shift_id' => $shift->id,
                'store_id' => $storeId,
                'payment_type' => $saleData['payment_type'],
                'total' => $saleData['total'],
                'discounts' => $saleData['discounts'] ?? [],
                'synced_at' => now(),
            ]);

            // 2️⃣ Décrémentation FIFO des stocks et enregistrement des mouvements
            foreach ($saleData['items'] as $itemData) {
                $remainingQty = $itemData['quantity'];

                $batches = \App\Models\StockBatch::where('store_id', $storeId)
                    ->where('product_id', $itemData['product_id'])
                    ->where('quantity', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingQty <= 0) break;

                    $deduct = min($batch->quantity, $remainingQty);

                    $batch->decrement('quantity', $deduct);

                    \App\Models\StockTransaction::create([
                        'stock_batch_id' => $batch->id,
                        'store_id' => $storeId,
                        'product_id' => $itemData['product_id'],
                        'type' => 'out',
                        'quantity' => $deduct,
                        'reason' => 'sale',
                        'sale_id' => $sale->id,
                        'shift_id' => $shift->id,
                    ]);

                    $remainingQty -= $deduct;
                }

                if ($remainingQty > 0) {
                    // Optionnel : déclencher une exception ou log si stock insuffisant
                }

                // 3️⃣ Création de la transaction financière par vente
                $lastTransaction = \App\Models\FinancialTransaction::where('store_id', $storeId)
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $balanceBefore = $lastTransaction?->balance_after ?? 0;
                $balanceAfter = $balanceBefore + $saleData['total'];

                $paymentMethod = \App\Models\FinancialPaymentMethod::where('code', strtoupper($saleData['payment_type']))->first();
                $paymentMethodId = $paymentMethod ? $paymentMethod->id : 1;

                $financialTransaction = \App\Models\FinancialTransaction::create([
                    'store_id' => $storeId,
                    'account_id' => $financialAccount->id,
                    'amount' => $saleData['total'],
                    'currency' => 'EUR',
                    'direction' => 'credit',
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'label' => "Vente POS #{$sale->id}",
                    'description' => "Vente POS n°{$sale->id} au magasin {$store->name}, shift_id {$shift->id}, user_id {$userId}, paiement {$saleData['payment_type']}",
                    'status' => 'validated',
                    'transaction_date' => now(),
                    'payment_method_id' => $paymentMethodId,
                    'user_id' => $userId,
                    'external_reference' => $sale->id,
                ]);

                // 4️⃣ Lien de la vente avec la transaction financière
                $sale->financial_transaction_id = $financialTransaction->id;
                $sale->save();
            }

            $syncedSales[] = $saleData['id'];
        }

        return response()->json([
            'status' => 'success',
            'synced_sales' => $syncedSales
        ]);
    }


    public function catalog(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);
        $defaultPhoto = asset('images/no_picture.jpg');

        // Produits
        $products = Product::with(['brand', 'categories.parent', 'images', 'stockBatches'])
            ->get()
            ->map(function ($product) use ($store, $defaultPhoto) {
                $categories = $product->categories->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->translation()?->name ?? 'Cat' . $cat->id,
                    'slug' => $cat->slug ?? null,
                    'parent_id' => $cat->parent?->id ?? 0,
                ]);

                $photos = $product->images->count()
                    ? $product->images->map(fn($img) => [
                        'id' => $img->id,
                        'url' => asset('storage/' . $img->path),
                        'is_primary' => $img->is_primary,
                        'sort_order' => $img->sort_order,
                    ])
                    : [[
                        'id' => 0,
                        'url' => $defaultPhoto,
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]];

                return [
                    'id' => $product->id,
                    'ean' => $product->ean,
                    'name' => $product->name,
                    'description' => $product->description,
                    'slugs' => $product->slugs,
                    'price' => $product->price,
                    'price_btob' => $product->price_btob,
                    'brand' => $product->brand ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                    ] : null,
                    'categories' => $categories,
                    'photos' => $photos,
                    'total_stock' => $product->getTotalStock($store),
                ];
            });

        // Arborescence complète des catégories
        $categoriesTree = $this->buildCategoryTree();

        return response()->json([
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
            ],
            'paymentsMethod' => FinancialPaymentMethod::all(),
            'products' => $products,
            'category_tree' => $categoriesTree, // <-- nouvelle clé
        ]);
    }

    /**
     * Génère l'arborescence complète des catégories
     */
    protected function buildCategoryTree($parentId = null)
    {
        $categories = Category::with('translations')->where('parent_id', $parentId)->get();

        return $categories->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->translation()?->name ?? 'Cat' . $cat->id,
            'slug' => $cat->slug,
            'children' => $this->buildCategoryTree($cat->id),
        ]);
    }

    protected function decrementStockFIFO(int $storeId, int $productId, int $quantity): void
    {
        $remaining = $quantity;

        // On récupère les lots dans l’ordre d’entrée (FIFO = plus anciens d’abord)
        $batches = \App\Models\StockBatch::where('store_id', $storeId)
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $deduct = min($batch->quantity, $remaining);
            $batch->quantity -= $deduct;
            $batch->save();

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            \Log::warning("Stock insuffisant pour décrémenter", [
                'store_id'   => $storeId,
                'product_id' => $productId,
                'missing'    => $remaining,
            ]);
        }
    }

}
