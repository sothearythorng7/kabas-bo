<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\FinancialPaymentMethod;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\StockBatch;
use App\Models\StockTransaction;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'sales.*.split_payments' => 'nullable|array',
            'sales.*.split_payments.*.payment_type' => 'required|string',
            'sales.*.split_payments.*.amount' => 'required|numeric|min:0',
            'sales.*.items' => 'required|array|min:1',
            'sales.*.items.*.product_id' => 'nullable|exists:products,id',
            'sales.*.items.*.quantity' => 'required|integer|min:1',
            'sales.*.items.*.price' => 'required|numeric',
            'sales.*.items.*.discounts' => 'nullable|array',
            'sales.*.items.*.is_delivery' => 'nullable|boolean',
            'sales.*.items.*.delivery_address' => 'nullable|string',
        ]);

        $shift   = Shift::findOrFail($data['shift_id']);
        $storeId = $shift->store_id;
        $store   = $shift->store;
        $userId  = $shift->user_id;

        $financialAccount = FinancialAccount::where('code', '701')->first();
        if (!$financialAccount) {
            throw new \Exception("Le compte financier 701 est introuvable.");
        }

        // Précharger les moyens de paiement en map CODE => id
        $paymentMethods = FinancialPaymentMethod::select('id', 'code')->get()
            ->keyBy(fn ($pm) => strtoupper($pm->code));

        $syncedSales = [];

        DB::transaction(function () use ($data, $storeId, $store, $userId, $financialAccount, $paymentMethods, &$syncedSales, $shift) {
            // Récupérer le dernier solde une seule fois
            $lastTx = FinancialTransaction::where('store_id', $storeId)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $runningBalance = $lastTx?->balance_after ?? 0;

            foreach ($data['sales'] as $saleData) {
                // 1) Création de la vente
                $sale = Sale::create([
                    'shift_id'   => $shift->id,
                    'store_id'   => $storeId,
                    'payment_type' => $saleData['payment_type'],
                    'total'        => $saleData['total'],
                    'discounts'    => $saleData['discounts'] ?? [],
                    'split_payments' => $saleData['split_payments'] ?? null,
                    'synced_at'    => now(),
                ]);

                // 2) Items + décrément FIFO + transaction stock
                foreach ($saleData['items'] as $itemData) {
                    // Check if this is a delivery service item
                    $isDelivery = $itemData['is_delivery'] ?? false;

                    SaleItem::create([
                        'sale_id'   => $sale->id,
                        'product_id'=> $itemData['product_id'],
                        'quantity'  => $itemData['quantity'],
                        'price'     => $itemData['price'],
                        'discounts' => $itemData['discounts'] ?? [],
                        'is_delivery' => $isDelivery,
                        'delivery_address' => $isDelivery ? ($itemData['delivery_address'] ?? null) : null,
                    ]);

                    // Skip stock management for delivery service items
                    if ($isDelivery) {
                        continue;
                    }

                    // Décrémentation FIFO et mouvements de stock
                    $remainingQty = $itemData['quantity'];

                    $batches = StockBatch::where('store_id', $storeId)
                        ->where('product_id', $itemData['product_id'])
                        ->where('quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate() // éviter les courses
                        ->get();

                    foreach ($batches as $batch) {
                        if ($remainingQty <= 0) break;

                        $deduct = min($batch->quantity, $remainingQty);
                        if ($deduct <= 0) continue;

                        // update quantités
                        $batch->decrement('quantity', $deduct);

                        StockTransaction::create([
                            'stock_batch_id' => $batch->id,
                            'store_id'       => $storeId,
                            'product_id'     => $itemData['product_id'],
                            'type'           => 'out',
                            'quantity'       => $deduct,
                            'reason'         => 'sale',
                            'sale_id'        => $sale->id,
                            'shift_id'       => $shift->id,
                        ]);

                        $remainingQty -= $deduct;
                    }

                    if ($remainingQty > 0) {
                        Log::warning("Stock insuffisant pour la vente", [
                            'store_id'   => $storeId,
                            'product_id' => $itemData['product_id'],
                            'missing'    => $remainingQty,
                            'sale_id'    => $sale->id,
                        ]);
                    }
                }

                // 3) Transaction(s) financière(s) - handle split payments
                $splitPayments = $saleData['split_payments'] ?? null;

                if ($splitPayments && count($splitPayments) > 0) {
                    // Multiple payment methods - create one transaction per payment
                    $firstTransactionId = null;

                    foreach ($splitPayments as $payment) {
                        $balanceBefore = $runningBalance;
                        $runningBalance += $payment['amount'];
                        $balanceAfter = $runningBalance;

                        $code = strtoupper($payment['payment_type']);
                        $paymentMethodId = $paymentMethods[$code]->id ?? ($paymentMethods['CASH']->id ?? 1);

                        $paymentMethodName = $paymentMethods[$code]->name ?? $payment['payment_type'];

                        $financialTransaction = FinancialTransaction::create([
                            'store_id'         => $storeId,
                            'account_id'       => $financialAccount->id,
                            'amount'           => $payment['amount'],
                            'currency'         => 'EUR',
                            'direction'        => 'credit',
                            'balance_before'   => $balanceBefore,
                            'balance_after'    => $balanceAfter,
                            'label'            => "POS Sale #{$sale->id} - {$paymentMethodName}",
                            'description'      => "POS Sale #{$sale->id} at store {$store->name}, shift_id {$shift->id}, user_id {$userId}, split payment {$paymentMethodName}: {$payment['amount']} EUR",
                            'status'           => 'validated',
                            'transaction_date' => now(),
                            'payment_method_id'=> $paymentMethodId,
                            'user_id'          => $userId,
                            'external_reference'=> $sale->id,
                        ]);

                        if (!$firstTransactionId) {
                            $firstTransactionId = $financialTransaction->id;
                        }
                    }

                    // Link the first transaction to the sale
                    $sale->financial_transaction_id = $firstTransactionId;
                    $sale->save();

                } else {
                    // Single payment method - original behavior
                    $balanceBefore = $runningBalance;
                    $runningBalance += $saleData['total'];
                    $balanceAfter = $runningBalance;

                    $code = strtoupper($saleData['payment_type']);
                    $paymentMethodId = $paymentMethods[$code]->id ?? ($paymentMethods['CASH']->id ?? 1);

                    $financialTransaction = FinancialTransaction::create([
                        'store_id'         => $storeId,
                        'account_id'       => $financialAccount->id,
                        'amount'           => $saleData['total'],
                        'currency'         => 'EUR',
                        'direction'        => 'credit',
                        'balance_before'   => $balanceBefore,
                        'balance_after'    => $balanceAfter,
                        'label'            => "POS Sale #{$sale->id}",
                        'description'      => "POS Sale #{$sale->id} at store {$store->name}, shift_id {$shift->id}, user_id {$userId}, payment {$saleData['payment_type']}",
                        'status'           => 'validated',
                        'transaction_date' => now(),
                        'payment_method_id'=> $paymentMethodId,
                        'user_id'          => $userId,
                        'external_reference'=> $sale->id,
                    ]);

                    // Link transaction to sale
                    $sale->financial_transaction_id = $financialTransaction->id;
                    $sale->save();
                }

                $syncedSales[] = $saleData['id'];
            }
        });

        return response()->json([
            'status'       => 'success',
            'synced_sales' => $syncedSales
        ]);
    }

    public function catalog(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);

        // URL de fallback si un produit n'a pas de photo
        $defaultPhoto = url('images/no_picture.jpg');

        // Base publique du disque "public" (config/filesystems)
        $publicBase = rtrim(config('filesystems.disks.public.url') ?? url('storage'), '/');

        // 1) Stock total par produit pour CE store en 1 requête
        $stockByProduct = DB::table('stock_batches')
            ->select('product_id', DB::raw('SUM(quantity) AS total_qty'))
            ->where('store_id', $storeId)
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id'); // [product_id => total]

        // 2) Charger les produits + relations utiles, colonnes ciblées
        $products = Product::query()
            ->select(['id', 'ean', 'name', 'description', 'slugs', 'price', 'price_btob', 'brand_id'])
            ->with([
                'brand:id,name',
                'categories:id,parent_id',
                'categories.parent:id,parent_id',
                'categories.translations:id,category_id,locale,name',
                'images' => function ($q) {
                    $q->select('id','product_id','path','is_primary','sort_order')
                      ->orderByDesc('is_primary')
                      ->orderBy('sort_order');
                },
                'primaryImage:id,product_id,path,is_primary,sort_order',
            ])
            ->get();

        // 3) Mapper sans changer la structure JSON
        $productsJson = $products->map(function ($product) use ($defaultPhoto, $publicBase, $stockByProduct) {
            // Catégories
            $categories = $product->categories->map(function ($cat) {
                $name = optional($cat->translation())->name ?? ('Cat' . $cat->id);
                return [
                    'id'        => $cat->id,
                    'name'      => $name,
                    'slug'      => $cat->slug('en') ?? null,
                    'parent_id' => $cat->parent?->id ?? 0,
                ];
            });

            // Photos (déjà triées via DB)
            if ($product->images->count()) {
                $photos = $product->images->map(function ($img) use ($publicBase) {
                    $path = ltrim($img->path ?? '', '/');
                    return [
                        'id'         => $img->id,
                        'path'       => $path ?: null,
                        'url'        => $path ? ($publicBase . '/' . $path) : null,
                        'is_primary' => (bool) ($img->is_primary ?? false),
                        'sort_order' => (int) ($img->sort_order ?? 0),
                    ];
                })->values()->all();
            } else {
                $photos = [[
                    'id'         => 0,
                    'path'       => null,
                    'url'        => $defaultPhoto,
                    'is_primary' => true,
                    'sort_order' => 0,
                ]];
            }

            return [
                'id'          => $product->id,
                'ean'         => $product->ean,
                'name'        => $product->name,
                'description' => $product->description,
                'slugs'       => $product->slugs,
                'price'       => $product->price,
                'price_btob'  => $product->price_btob,
                'brand'       => $product->brand ? [
                    'id'   => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'categories'  => $categories,
                'photos'      => $photos,
                'total_stock' => (int) ($stockByProduct[$product->id] ?? 0),
            ];
        });

        // 4) Arborescence des catégories en 1 passe (même structure que l’ancienne)
        $categoriesTree = $this->categoryTreeFast();

        return response()->json([
            'store'          => [
                'id'   => $store->id,
                'name' => $store->name,
            ],
            'paymentsMethod' => FinancialPaymentMethod::select('id','code','name')->get(),
            'products'       => $productsJson,
            'category_tree'  => $categoriesTree,
        ]);
    }

    /**
     * Generates the complete category tree in 1 single query + in-memory construction.
     * Output structure IDENTICAL to buildCategoryTree().
     */
    protected function categoryTreeFast(): array
    {
        $cats = Category::query()
            ->select('id','parent_id')
            ->with(['translations:id,category_id,locale,name'])
            ->get();

        // [id => node]
        $nodes = [];
        foreach ($cats as $c) {
            $name = optional($c->translation())->name ?? ('Cat' . $c->id);
            $nodes[$c->id] = [
                'id'       => $c->id,
                'name'     => $name,
                'slug'     => $c->slug('en') ?? null,
                'children' => [],
                '_pid'     => $c->parent_id, // temporaire pour linkage
            ];
        }

        // Lier enfants → parents
        $roots = [];
        foreach ($nodes as $id => &$n) {
            $pid = $n['_pid'];
            unset($n['_pid']);
            if ($pid && isset($nodes[$pid])) {
                $nodes[$pid]['children'][] = &$n;
            } else {
                $roots[] = &$n;
            }
        }
        unset($n);

        return $roots;
    }

    /**
     * Original version (not used) — kept for reference.
     */
    protected function buildCategoryTree($parentId = null)
    {
        $categories = Category::with('translations')->where('parent_id', $parentId)->get();

        return $categories->map(fn($cat) => [
            'id'       => $cat->id,
            'name'     => $cat->translation()?->name ?? 'Cat' . $cat->id,
            'slug'     => $cat->slug('en') ?? null,
            'children' => $this->buildCategoryTree($cat->id),
        ]);
    }

    protected function decrementStockFIFO(int $storeId, int $productId, int $quantity): void
    {
        $remaining = $quantity;

        $batches = StockBatch::where('store_id', $storeId)
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
            Log::warning("Insufficient stock to decrement", [
                'store_id'   => $storeId,
                'product_id' => $productId,
                'missing'    => $remaining,
            ]);
        }
    }
}
