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
use Illuminate\Support\Facades\Config;
use App\Events\SaleCreated;
use App\Models\Voucher;
use App\Services\VoucherService;

class SyncController extends Controller
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }
    public function users()
    {
        return User::select('id', 'name', 'pin_code', 'store_id')
            ->whereNotNull('pin_code')
            ->whereNotNull('store_id')
            ->get();
    }

    public function shifts(Request $request)
    {
        $data = $request->validate([
            'shifts' => 'required|array',
            'shifts.*.user_id' => 'required|exists:users,id',
            'shifts.*.store_id' => 'required|exists:stores,id',
            'shifts.*.cash_start' => 'required|numeric',
            'shifts.*.cash_end' => 'nullable|numeric',
            'shifts.*.visitors_count' => 'nullable|integer',
            'shifts.*.cash_difference' => 'nullable|numeric',
            'shifts.*.started_at' => 'required|date',
            'shifts.*.ended_at' => 'nullable|date',
        ]);

        $syncedShifts = [];

        foreach ($data['shifts'] as $shiftData) {
            // Check if shift already exists
            $existingShift = Shift::where('user_id', $shiftData['user_id'])
                ->where('started_at', $shiftData['started_at'])
                ->first();

            if ($existingShift) {
                // Update existing shift
                $existingShift->update([
                    'closing_cash' => $shiftData['cash_end'] ?? null,
                    'visitors_count' => $shiftData['visitors_count'] ?? null,
                    'cash_difference' => $shiftData['cash_difference'] ?? null,
                    'ended_at' => $shiftData['ended_at'] ?? null,
                    'synced' => true,
                ]);
                $syncedShifts[] = $existingShift->id;
            } else {
                // Create new shift
                $shift = Shift::create([
                    'user_id' => $shiftData['user_id'],
                    'store_id' => $shiftData['store_id'],
                    'opening_cash' => $shiftData['cash_start'],
                    'closing_cash' => $shiftData['cash_end'] ?? null,
                    'visitors_count' => $shiftData['visitors_count'] ?? null,
                    'cash_difference' => $shiftData['cash_difference'] ?? null,
                    'started_at' => $shiftData['started_at'],
                    'ended_at' => $shiftData['ended_at'] ?? null,
                    'synced' => true,
                ]);
                $syncedShifts[] = $shift->id;
            }
        }

        return response()->json([
            'status' => 'success',
            'synced_shifts' => $syncedShifts,
        ]);
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
            'sales.*.split_payments.*.voucher_code' => 'nullable|string',
            'sales.*.voucher_code' => 'nullable|string',
            'sales.*.items' => 'required|array|min:1',
            'sales.*.items.*.product_id' => 'nullable|exists:products,id',
            'sales.*.items.*.quantity' => 'required|integer|min:1',
            'sales.*.items.*.price' => 'required|numeric',
            'sales.*.items.*.discounts' => 'nullable|array',
            'sales.*.items.*.is_delivery' => 'nullable|boolean',
            'sales.*.items.*.delivery_address' => 'nullable|string',
            'sales.*.items.*.is_custom_service' => 'nullable|boolean',
            'sales.*.items.*.custom_service_description' => 'nullable|string',
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
                $calculatedTotal = $this->calculateSaleTotal($saleData);

                // Protection anti-doublon: vérifier si cette vente a déjà été synchronisée
                // On cherche une vente avec le même shift_id, même total, créée dans les 2 dernières minutes
                // puis on compare les product_ids des items
                $itemProductIds = collect($saleData['items'])
                    ->pluck('product_id')
                    ->filter()
                    ->sort()
                    ->values()
                    ->toJson();

                $potentialDuplicates = Sale::with('items:id,sale_id,product_id')
                    ->where('shift_id', $shift->id)
                    ->where('total', $calculatedTotal)
                    ->where('created_at', '>=', now()->subMinutes(2))
                    ->get();

                $existingSale = $potentialDuplicates->first(function ($sale) use ($itemProductIds, $saleData) {
                    // Vérifier que le nombre d'items correspond
                    if ($sale->items->count() !== count($saleData['items'])) {
                        return false;
                    }
                    // Comparer les product_ids triés
                    $saleProductIds = $sale->items
                        ->pluck('product_id')
                        ->filter()
                        ->sort()
                        ->values()
                        ->toJson();
                    return $saleProductIds === $itemProductIds;
                });

                if ($existingSale) {
                    Log::info("Doublon détecté - vente ignorée", [
                        'existing_sale_id' => $existingSale->id,
                        'shift_id' => $shift->id,
                        'total' => $calculatedTotal,
                        'pos_local_id' => $saleData['id'],
                    ]);
                    $syncedSales[] = $saleData['id']; // On retourne quand même l'ID pour que le POS marque la vente comme synchronisée
                    continue;
                }

                // 1) Création de la vente
                $sale = Sale::create([
                    'shift_id'   => $shift->id,
                    'store_id'   => $storeId,
                    'payment_type' => $saleData['payment_type'],
                    'total'        => $calculatedTotal,
                    'discounts'    => $saleData['discounts'] ?? [],
                    'split_payments' => $saleData['split_payments'] ?? null,
                    'synced_at'    => now(),
                ]);

                // 2) Items + décrément FIFO + transaction stock
                foreach ($saleData['items'] as $itemData) {
                    // Check if this is a delivery service item or custom service
                    $isDelivery = $itemData['is_delivery'] ?? false;
                    $isCustomService = $itemData['is_custom_service'] ?? false;

                    SaleItem::create([
                        'sale_id'   => $sale->id,
                        'product_id'=> $itemData['product_id'],
                        'quantity'  => $itemData['quantity'],
                        'price'     => $itemData['price'],
                        'discounts' => $itemData['discounts'] ?? [],
                        'is_delivery' => $isDelivery,
                        'delivery_address' => $isDelivery ? ($itemData['delivery_address'] ?? null) : null,
                        'is_custom_service' => $isCustomService,
                        'custom_service_description' => $isCustomService ? ($itemData['custom_service_description'] ?? null) : null,
                    ]);

                    // Skip stock management for delivery and custom service items
                    if ($isDelivery || $isCustomService) {
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
                        $code = strtoupper($payment['payment_type']);

                        // Handle voucher payment
                        if ($code === 'VOUCHER' && !empty($payment['voucher_code'])) {
                            $voucherResult = $this->voucherService->validate($payment['voucher_code']);
                            if ($voucherResult['valid']) {
                                $this->voucherService->applyToSale($voucherResult['voucher'], $sale, $store);
                            } else {
                                Log::warning("Invalid voucher in split payment", [
                                    'voucher_code' => $payment['voucher_code'],
                                    'error' => $voucherResult['error'],
                                    'sale_id' => $sale->id,
                                ]);
                            }
                            // Voucher payments don't create financial transactions (already tracked in vouchers table)
                            continue;
                        }

                        $balanceBefore = $runningBalance;
                        $runningBalance += $payment['amount'];
                        $balanceAfter = $runningBalance;

                        $paymentMethodId = $paymentMethods[$code]->id ?? ($paymentMethods['CASH']->id ?? 1);

                        $paymentMethodName = $paymentMethods[$code]->name ?? $payment['payment_type'];

                        $financialTransaction = FinancialTransaction::create([
                            'store_id'         => $storeId,
                            'account_id'       => $financialAccount->id,
                            'amount'           => $payment['amount'],
                            'currency'         => 'USD',
                            'direction'        => 'credit',
                            'balance_before'   => $balanceBefore,
                            'balance_after'    => $balanceAfter,
                            'label'            => "POS Sale #{$sale->id} - {$paymentMethodName}",
                            'description'      => "POS Sale #{$sale->id} at store {$store->name}, shift_id {$shift->id}, user_id {$userId}, split payment {$paymentMethodName}: {$payment['amount']} $",
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
                    if ($firstTransactionId) {
                        $sale->financial_transaction_id = $firstTransactionId;
                        $sale->save();
                    }

                } else {
                    // Single payment method
                    $code = strtoupper($saleData['payment_type']);

                    // Handle single voucher payment
                    if ($code === 'VOUCHER' && !empty($saleData['voucher_code'])) {
                        $voucherResult = $this->voucherService->validate($saleData['voucher_code']);
                        if ($voucherResult['valid']) {
                            $this->voucherService->applyToSale($voucherResult['voucher'], $sale, $store);
                        } else {
                            Log::warning("Invalid voucher payment", [
                                'voucher_code' => $saleData['voucher_code'],
                                'error' => $voucherResult['error'],
                                'sale_id' => $sale->id,
                            ]);
                        }
                        // Voucher payments don't create financial transactions
                    } else {
                        // Regular payment - create financial transaction
                        $balanceBefore = $runningBalance;
                        $runningBalance += $calculatedTotal;
                        $balanceAfter = $runningBalance;

                        $paymentMethodId = $paymentMethods[$code]->id ?? ($paymentMethods['CASH']->id ?? 1);

                        $financialTransaction = FinancialTransaction::create([
                            'store_id'         => $storeId,
                            'account_id'       => $financialAccount->id,
                            'amount'           => $calculatedTotal,
                            'currency'         => 'USD',
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
                }

                // Dispatch event for Telegram notification
                SaleCreated::dispatch($sale);

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

    /**
     * Recherche de produits via Meilisearch pour le POS
     */
    public function search(Request $request, $storeId)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 20);

        // URL de fallback si un produit n'a pas de photo
        $defaultPhoto = url('images/no_picture.jpg');
        $publicBase = rtrim(config('filesystems.disks.public.url') ?? url('storage'), '/');

        // Stock par produit pour ce store
        $stockByProduct = DB::table('stock_batches')
            ->select('product_id', DB::raw('SUM(quantity) AS total_qty'))
            ->where('store_id', $storeId)
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        // Recherche via Meilisearch si disponible
        if (Config::get('scout.driver') === 'meilisearch') {
            $products = Product::search($query)
                ->take($limit)
                ->get()
                ->load(['brand:id,name', 'primaryImage:id,product_id,path']);
        } else {
            // Fallback SQL
            $products = Product::query()
                ->where(function ($q) use ($query) {
                    $q->where('ean', 'like', "%{$query}%")
                      ->orWhere('name->fr', 'like', "%{$query}%")
                      ->orWhere('name->en', 'like', "%{$query}%");
                })
                ->with(['brand:id,name', 'primaryImage:id,product_id,path'])
                ->limit($limit)
                ->get();
        }

        // Formater la réponse
        $results = $products->map(function ($product) use ($defaultPhoto, $publicBase, $stockByProduct) {
            $imagePath = $product->primaryImage?->path;
            $imageUrl = $imagePath ? ($publicBase . '/' . ltrim($imagePath, '/')) : $defaultPhoto;

            return [
                'id'          => $product->id,
                'ean'         => $product->ean,
                'name'        => $product->name,
                'price'       => $product->price,
                'brand'       => $product->brand ? [
                    'id'   => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'image_url'   => $imageUrl,
                'total_stock' => (int) ($stockByProduct[$product->id] ?? 0),
            ];
        });

        return response()->json([
            'query'   => $query,
            'count'   => $results->count(),
            'results' => $results,
        ]);
    }

    /**
     * Calculate the correct sale total after applying all discounts
     */
    private function calculateSaleTotal(array $saleData): float
    {
        $itemsTotal = 0;
        $totalDiscounts = 0;

        foreach ($saleData['items'] as $item) {
            $lineGross = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
            $itemsTotal += $lineGross;

            foreach ($item['discounts'] ?? [] as $d) {
                if (($d['type'] ?? '') === 'amount') {
                    // Check scope: 'unit' means per unit, otherwise per line
                    if (($d['scope'] ?? 'line') === 'unit') {
                        $totalDiscounts += ($d['value'] ?? 0) * ($item['quantity'] ?? 1);
                    } else {
                        $totalDiscounts += $d['value'] ?? 0;
                    }
                } elseif (($d['type'] ?? '') === 'percent') {
                    $totalDiscounts += (($d['value'] ?? 0) / 100) * $lineGross;
                }
            }
        }

        // Apply sale-level discounts
        foreach ($saleData['discounts'] ?? [] as $d) {
            if (($d['type'] ?? '') === 'amount') {
                $totalDiscounts += $d['value'] ?? 0;
            } elseif (($d['type'] ?? '') === 'percent') {
                $totalDiscounts += (($d['value'] ?? 0) / 100) * $itemsTotal;
            }
        }

        return round($itemsTotal - $totalDiscounts, 2);
    }
}
