<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Reseller;
use App\Models\ResellerProductPrice;
use App\Models\ResellerStockDelivery;
use App\Models\StockBatch;
use App\Models\StockTransaction;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\FinancialPaymentMethod;
use App\Models\ResellerInvoice;

class ResellerStockDeliveryController extends Controller
{
    // Création de livraison
    public function create($reseller)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            $shop = Store::findOrFail($shopId);
            $resellerObj = (object)[
                'id' => $reseller,
                'name' => $shop->name,
                'type' => 'shop',
                'is_shop' => true,
            ];
        } else {
            $resellerObj = Reseller::findOrFail($reseller);
        }

        $warehouse = Store::warehouse()->first();

        $query = Product::query();
        if (!$isShop) {
            $query->where('is_resalable', true);
        }

        // Récupérer les prix personnalisés pour ce revendeur
        $resellerPrices = [];
        if (!$isShop && is_numeric($reseller)) {
            $resellerPrices = ResellerProductPrice::where('reseller_id', $reseller)
                ->pluck('price', 'product_id')
                ->toArray();
        }

        $products = $query->get()->map(function($product) use ($warehouse, $resellerPrices) {
            $product->available_stock = $warehouse
                ? $product->stockBatches()->where('store_id', $warehouse->id)->sum('quantity')
                : 0;
            // Utiliser le prix personnalisé du revendeur s'il existe
            $product->reseller_price = $resellerPrices[$product->id] ?? $product->price_btob ?? $product->price;
            return $product;
        });

        return view('resellers.deliveries.create', [
            'reseller' => $resellerObj,
            'products' => $products,
        ]);
    }

    // Affichage d'une livraison
    public function show($reseller, ResellerStockDelivery $delivery)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            $shop = Store::findOrFail($shopId);
            $resellerObj = (object)[
                'id' => $reseller,
                'name' => $shop->name,
                'type' => 'shop',
                'is_shop' => true,
            ];

            if ($delivery->store_id !== $shopId) abort(404);
        } else {
            $resellerObj = Reseller::findOrFail($reseller);
            if ($delivery->reseller_id !== (int)$reseller) abort(404);
        }

        $delivery->load('products', 'reseller');

        return view('resellers.deliveries.show', [
            'reseller' => $resellerObj,
            'delivery' => $delivery,
        ]);
    }

    // Edition d'une livraison
    public function edit($reseller, ResellerStockDelivery $delivery)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            $shop = Store::findOrFail($shopId);
            $resellerObj = (object)[
                'id' => $reseller,
                'name' => $shop->name,
                'type' => 'shop',
                'is_shop' => true,
            ];

            if ($delivery->store_id !== $shopId) abort(404);
        } else {
            $resellerObj = Reseller::findOrFail($reseller);
            if ($delivery->reseller_id !== (int)$reseller) abort(404);
        }

        $delivery->load('products');
        $paymentMethods = FinancialPaymentMethod::all();

        $warehouseProducts = collect();
        if ($delivery->status === 'draft') {
            $warehouse = Store::warehouse()->first();
            $existingProductIds = $delivery->products->pluck('id')->toArray();

            $resellerPrices = [];
            if (!$isShop && is_numeric($reseller)) {
                $resellerPrices = ResellerProductPrice::where('reseller_id', $reseller)
                    ->pluck('price', 'product_id')
                    ->toArray();
            }

            $query = Product::query();
            if (!$isShop) {
                $query->where('is_resalable', true);
            }

            $warehouseProducts = $query->whereNotIn('id', $existingProductIds)
                ->get()
                ->map(function ($product) use ($warehouse, $resellerPrices) {
                    $product->available_stock = $warehouse
                        ? $product->stockBatches()->where('store_id', $warehouse->id)->sum('quantity')
                        : 0;
                    $product->reseller_price = $resellerPrices[$product->id] ?? $product->price_btob ?? $product->price;
                    return $product;
                })
                ->filter(fn ($product) => $product->available_stock > 0);

            // Also compute available stock for existing products (for max input)
            foreach ($delivery->products as $product) {
                $product->available_stock = $warehouse
                    ? $product->stockBatches()->where('store_id', $warehouse->id)->sum('quantity')
                    : 0;
            }
        }

        return view('resellers.deliveries.edit', [
            'reseller' => $resellerObj,
            'delivery' => $delivery,
            'paymentMethods' => $paymentMethods,
            'warehouseProducts' => $warehouseProducts,
        ]);
    }

    // Enregistrement d'une nouvelle livraison
    public function store(Request $request, $reseller)
    {
        $isShop = str_starts_with($reseller, 'shop-');
        $warehouse = Store::warehouse()->first();

        if (!$warehouse) {
            return back()->withErrors('Aucun warehouse défini.');
        }

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            $shop = Store::findOrFail($shopId);
            $resellerId = null;
        } else {
            $resellerModel = Reseller::findOrFail($reseller);
            $resellerId = $resellerModel->id;
            $shopId = null;
        }

        $validated = $request->validate([
            'delivered_at' => 'nullable|date',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'nullable|integer|min:0',
            'products.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        $productsToDeliver = collect($validated['products'])->filter(fn($p) => !empty($p['quantity']));

        if ($productsToDeliver->isEmpty()) {
            return back()->withErrors(['products' => 'You must specify a quantity for at least one product.'])->withInput();
        }

        foreach ($productsToDeliver as $p) {
            if (!isset($p['unit_price'])) {
                return back()->withErrors(['products' => "Unit price is required for product ID {$p['id']}"])->withInput();
            }
        }

        DB::transaction(function () use ($resellerId, $shopId, $productsToDeliver, $validated, $warehouse) {
            $delivery = ResellerStockDelivery::create([
                'reseller_id' => $resellerId,
                'store_id' => $shopId,
                'delivered_at' => $validated['delivered_at'] ?? null,
                'status' => 'draft',
            ]);

            foreach ($productsToDeliver as $p) {
                $product = Product::findOrFail($p['id']);
                $quantity = $p['quantity'];

                // Vérification et déduction du stock depuis le warehouse (FIFO)
                $remaining = $quantity;
                $batches = $product->stockBatches()
                    ->where('store_id', $warehouse->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('created_at')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $toDeduct = min($batch->quantity, $remaining);
                    $batch->quantity -= $toDeduct;
                    $batch->save();

                    // Transaction stock FIFO
                    StockTransaction::create([
                        'stock_batch_id' => $batch->id,
                        'store_id'       => $warehouse->id,
                        'product_id'     => $product->id,
                        'type'           => 'out',
                        'quantity'       => $toDeduct,
                        'reason'         => 'reseller_delivery',
                        'user_id'        => auth()->id(),
                    ]);

                    $remaining -= $toDeduct;
                }

                if ($remaining > 0) {
                    throw new \Exception("Stock insuffisant pour le produit {$product->name}");
                }

                $delivery->products()->attach($product->id, [
                    'quantity' => $quantity,
                    'unit_price' => $p['unit_price'],
                ]);
            }
        });

        return redirect()->route('resellers.show', $reseller)->with('success', 'Delivery created successfully.');
    }

    // Mise à jour d'une livraison
    public function update(Request $request, $reseller, ResellerStockDelivery $delivery)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            if ($delivery->store_id !== $shopId) abort(404);
        } else {
            if ($delivery->reseller_id !== (int)$reseller) abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ResellerStockDelivery::STATUS_OPTIONS))],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($delivery, $reseller, $isShop, $validated) {
            $oldStatus = $delivery->status;
            $delivery->update($validated);

            // Si la livraison passe à "shipped"
            if ($oldStatus !== 'shipped' && $delivery->status === 'shipped') {
                foreach ($delivery->products as $product) {
                    if ($isShop) {
                        $shopId = (int) str_replace('shop-', '', $reseller);
                        $batch = StockBatch::create([
                            'store_id' => $shopId,
                            'reseller_id' => null,
                            'product_id' => $product->id,
                            'quantity' => $product->pivot->quantity,
                            'unit_price' => $product->pivot->unit_price,
                            'source_delivery_id' => $delivery->id,
                        ]);
                    } else {
                        $batch = StockBatch::create([
                            'store_id' => null,
                            'reseller_id' => $reseller,
                            'product_id' => $product->id,
                            'quantity' => $product->pivot->quantity,
                            'unit_price' => $product->pivot->unit_price,
                            'source_delivery_id' => $delivery->id,
                        ]);
                    }

                    // Transaction d'entrée stock (seulement pour les shops, pas les revendeurs externes)
                    if ($batch->store_id) {
                        StockTransaction::create([
                            'stock_batch_id' => $batch->id,
                            'store_id'       => $batch->store_id,
                            'product_id'     => $product->id,
                            'type'           => 'in',
                            'quantity'       => $product->pivot->quantity,
                            'reason'         => 'reseller_delivery_received',
                            'user_id'        => auth()->id(),
                        ]);
                    }
                }
            }
        });

        return redirect()->route('resellers.show', $reseller)->with('success', 'Delivery updated successfully.');
    }

    // Mise à jour des produits d'une livraison draft
    public function updateProducts(Request $request, $reseller, ResellerStockDelivery $delivery)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            if ($delivery->store_id !== $shopId) abort(404);
        } else {
            if ($delivery->reseller_id !== (int)$reseller) abort(404);
        }

        if ($delivery->status !== 'draft') {
            return back()->withErrors(['products' => __('messages.resellers.delivery_not_draft')]);
        }

        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'nullable|integer|min:0',
            'products.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        $warehouse = Store::warehouse()->first();
        if (!$warehouse) {
            return back()->withErrors('No warehouse defined.');
        }

        $delivery->load('products');

        $submittedProducts = collect($validated['products'])->keyBy('id');
        $currentProducts = $delivery->products->keyBy('id');

        // Check stock availability before making any changes
        foreach ($submittedProducts as $productId => $data) {
            $qty = (int)($data['quantity'] ?? 0);
            if ($qty <= 0) continue;

            $product = Product::find($productId);
            if (!$product) continue;

            if ($currentProducts->has($productId)) {
                $currentQty = $currentProducts[$productId]->pivot->quantity;
                $diff = $qty - $currentQty;
                if ($diff > 0) {
                    $availableStock = $product->stockBatches()
                        ->where('store_id', $warehouse->id)
                        ->sum('quantity');
                    if ($availableStock < $diff) {
                        $name = $product->name[app()->getLocale()] ?? reset($product->name);
                        return back()->withErrors(['products' => __('messages.resellers.insufficient_stock', ['name' => $name])])->withInput();
                    }
                }
            } else {
                $availableStock = $product->stockBatches()
                    ->where('store_id', $warehouse->id)
                    ->sum('quantity');
                if ($availableStock < $qty) {
                    $name = $product->name[app()->getLocale()] ?? reset($product->name);
                    return back()->withErrors(['products' => __('messages.resellers.insufficient_stock', ['name' => $name])])->withInput();
                }
            }
        }

        DB::transaction(function () use ($delivery, $submittedProducts, $currentProducts, $warehouse) {
            $syncData = [];

            // Process current products
            foreach ($currentProducts as $productId => $product) {
                if (!$submittedProducts->has($productId) || (int)($submittedProducts[$productId]['quantity'] ?? 0) <= 0) {
                    // Product removed: restore stock
                    $this->restoreStock($productId, $product->pivot->quantity, $warehouse);
                    // Will not be included in sync → detached
                } else {
                    $newQty = (int)$submittedProducts[$productId]['quantity'];
                    $newPrice = (float)$submittedProducts[$productId]['unit_price'];
                    $currentQty = $product->pivot->quantity;

                    if ($newQty > $currentQty) {
                        $this->deductStock($productId, $newQty - $currentQty, $warehouse);
                    } elseif ($newQty < $currentQty) {
                        $this->restoreStock($productId, $currentQty - $newQty, $warehouse);
                    }

                    $syncData[$productId] = [
                        'quantity' => $newQty,
                        'unit_price' => $newPrice,
                    ];
                }
            }

            // Process new products
            foreach ($submittedProducts as $productId => $data) {
                if ($currentProducts->has($productId)) continue;

                $qty = (int)($data['quantity'] ?? 0);
                if ($qty <= 0) continue;

                if (!isset($data['unit_price'])) continue;

                $this->deductStock($productId, $qty, $warehouse);

                $syncData[$productId] = [
                    'quantity' => $qty,
                    'unit_price' => (float)$data['unit_price'],
                ];
            }

            $delivery->products()->sync($syncData);

            // Update invoice total if exists
            if ($delivery->invoice) {
                $totalAmount = collect($syncData)->sum(fn ($d) => $d['quantity'] * $d['unit_price']);
                $totalAmount += $delivery->shipping_cost ?? 0;
                $delivery->invoice->update(['total_amount' => $totalAmount]);
            }
        });

        return redirect()->route('reseller-stock-deliveries.edit', [$reseller, $delivery->id])
            ->with('success', __('messages.resellers.products_updated'));
    }

    private function deductStock($productId, $quantity, $warehouse)
    {
        $product = Product::findOrFail($productId);
        $remaining = $quantity;
        $batches = $product->stockBatches()
            ->where('store_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->orderBy('created_at')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $toDeduct = min($batch->quantity, $remaining);
            $batch->quantity -= $toDeduct;
            $batch->save();

            StockTransaction::create([
                'stock_batch_id' => $batch->id,
                'store_id'       => $warehouse->id,
                'product_id'     => $product->id,
                'type'           => 'out',
                'quantity'       => $toDeduct,
                'reason'         => 'reseller_delivery',
                'user_id'        => auth()->id(),
            ]);

            $remaining -= $toDeduct;
        }

        if ($remaining > 0) {
            throw new \Exception("Insufficient stock for product {$product->name}");
        }
    }

    private function restoreStock($productId, $quantity, $warehouse)
    {
        $product = Product::findOrFail($productId);
        $batch = $product->stockBatches()
            ->where('store_id', $warehouse->id)
            ->orderByDesc('created_at')
            ->first();

        if ($batch) {
            $batch->quantity += $quantity;
            $batch->save();
        } else {
            $batch = StockBatch::create([
                'store_id'   => $warehouse->id,
                'product_id' => $productId,
                'quantity'   => $quantity,
                'unit_price' => $product->price,
            ]);
        }

        StockTransaction::create([
            'stock_batch_id' => $batch->id,
            'store_id'       => $warehouse->id,
            'product_id'     => $productId,
            'type'           => 'in',
            'quantity'       => $quantity,
            'reason'         => 'reseller_delivery_adjustment',
            'user_id'        => auth()->id(),
        ]);
    }

    // Méthode pour récupérer toutes les livraisons d'un revendeur ou d'un shop
    public static function getDeliveries($reseller)
    {
        if (str_starts_with($reseller, 'shop-')) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            return ResellerStockDelivery::where('store_id', $shopId)->latest()->paginate(10);
        } else {
            $resellerId = (int)$reseller;
            return ResellerStockDelivery::where('reseller_id', $resellerId)->latest()->paginate(10);
        }
    }

    // Upload delivery note
    public function uploadDeliveryNote(Request $request, $reseller, ResellerStockDelivery $delivery)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            if ($delivery->store_id !== $shopId) abort(404);
        } else {
            if ($delivery->reseller_id !== (int)$reseller) abort(404);
        }

        $request->validate([
            'delivery_note' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        // Delete old file if exists
        if ($delivery->delivery_note && \Storage::disk('public')->exists($delivery->delivery_note)) {
            \Storage::disk('public')->delete($delivery->delivery_note);
        }

        // Store new file
        $path = $request->file('delivery_note')->store('delivery-notes', 'public');
        $delivery->update(['delivery_note' => $path]);

        return back()->with('success', __('messages.resellers.delivery_note_uploaded'));
    }

    // Delete delivery note
    public function deleteDeliveryNote($reseller, ResellerStockDelivery $delivery)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            if ($delivery->store_id !== $shopId) abort(404);
        } else {
            if ($delivery->reseller_id !== (int)$reseller) abort(404);
        }

        if ($delivery->delivery_note && \Storage::disk('public')->exists($delivery->delivery_note)) {
            \Storage::disk('public')->delete($delivery->delivery_note);
        }

        $delivery->update(['delivery_note' => null]);

        return back()->with('success', __('messages.resellers.delivery_note_deleted'));
    }

    // Generate delivery note PDF
    public function generateDeliveryNote($reseller, ResellerStockDelivery $delivery)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            $shop = Store::findOrFail($shopId);
            $resellerObj = (object)[
                'id' => $reseller,
                'name' => $shop->name,
                'type' => 'shop',
                'is_shop' => true,
                'contacts' => collect(),
            ];

            if ($delivery->store_id !== $shopId) abort(404);
        } else {
            $resellerObj = Reseller::with('contacts')->findOrFail($reseller);
            if ($delivery->reseller_id !== (int)$reseller) abort(404);
        }

        $delivery->load('products');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('resellers.deliveries.delivery-note-pdf', [
            'delivery' => $delivery,
            'reseller' => $resellerObj,
        ]);

        $filename = 'delivery_note_' . $delivery->id . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    // Create invoice for delivery
    public function createInvoice($reseller, ResellerStockDelivery $delivery)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            if ($delivery->store_id !== $shopId) abort(404);
            $resellerId = null;
            $storeId = $shopId;
        } else {
            if ($delivery->reseller_id !== (int)$reseller) abort(404);
            $resellerId = (int)$reseller;
            $storeId = null;
        }

        // Check if invoice already exists
        if ($delivery->invoice) {
            return back()->with('info', __('messages.resellers.invoice_already_exists'));
        }

        // Calculate total amount
        $totalAmount = $delivery->products->sum(function ($product) {
            return $product->pivot->quantity * $product->pivot->unit_price;
        });

        // Add shipping cost if any
        $totalAmount += $delivery->shipping_cost ?? 0;

        // Create invoice
        ResellerInvoice::create([
            'reseller_id' => $resellerId,
            'store_id' => $storeId,
            'reseller_stock_delivery_id' => $delivery->id,
            'total_amount' => $totalAmount,
            'status' => 'unpaid',
        ]);

        return back()->with('success', __('messages.resellers.invoice_created'));
    }

    // Generate invoice PDF
    public function generateInvoicePdf($reseller, ResellerStockDelivery $delivery)
    {
        $isShop = str_starts_with($reseller, 'shop-');

        if ($isShop) {
            $shopId = (int) str_replace('shop-', '', $reseller);
            $shop = Store::findOrFail($shopId);
            $resellerObj = (object)[
                'id' => $reseller,
                'name' => $shop->name,
                'type' => 'shop',
                'is_shop' => true,
            ];

            if ($delivery->store_id !== $shopId) abort(404);
        } else {
            $resellerObj = Reseller::findOrFail($reseller);
            if ($delivery->reseller_id !== (int)$reseller) abort(404);
        }

        $delivery->load('products.brand', 'products.suppliers');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('resellers.deliveries.invoice-pdf', [
            'delivery' => $delivery,
            'reseller' => $resellerObj,
        ]);

        $filename = 'invoice_' . $delivery->id . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }
}
