<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Reseller;
use App\Models\ResellerStockDelivery;
use App\Models\StockBatch;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResellerStockDeliveryController extends Controller
{
    // Création de livraison
    public function create($reseller)
    {
        if (str_starts_with($reseller, 'shop-')) {
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

        $products = Product::where('is_resalable', true)->get();

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

            if ($delivery->store_id !== $shopId) {
                abort(404);
            }
        } else {
            $resellerModel = Reseller::findOrFail($reseller);
            $resellerObj = $resellerModel;

            if ($delivery->reseller_id !== (int)$reseller) {
                abort(404);
            }
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

            if ($delivery->store_id !== $shopId) {
                abort(404);
            }
        } else {
            $resellerObj = Reseller::findOrFail($reseller);

            if ($delivery->reseller_id !== (int)$reseller) {
                abort(404);
            }
        }

        $delivery->load('products');

        return view('resellers.deliveries.edit', [
            'reseller' => $resellerObj,
            'delivery' => $delivery,
        ]);
    }

    // Enregistrement d'une nouvelle livraison
    public function store(Request $request, $reseller)
    {
        $isShop = str_starts_with($reseller, 'shop-');

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

        $productsToDeliver = collect($validated['products'])
            ->filter(fn($p) => !empty($p['quantity']));

        if ($productsToDeliver->isEmpty()) {
            return back()->withErrors(['products' => 'You must specify a quantity for at least one product.'])->withInput();
        }

        foreach ($productsToDeliver as $p) {
            if (!isset($p['unit_price'])) {
                return back()->withErrors(['products' => "Unit price is required for product ID {$p['id']}"])->withInput();
            }
        }

        DB::transaction(function () use ($resellerId, $shopId, $productsToDeliver, $validated) {
            $delivery = ResellerStockDelivery::create([
                'reseller_id' => $resellerId,
                'store_id' => $shopId,
                'delivered_at' => $validated['delivered_at'] ?? null,
                'status' => 'draft',
            ]);

            foreach ($productsToDeliver as $p) {
                $delivery->products()->attach($p['id'], [
                    'quantity' => $p['quantity'],
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
            if ($delivery->store_id !== $shopId) {
                abort(404);
            }
        } else {
            if ($delivery->reseller_id !== (int)$reseller) {
                abort(404);
            }
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

                        \App\Models\StockBatch::create([
                            'store_id' => $shopId,
                            'reseller_id' => null,
                            'product_id' => $product->id,
                            'quantity' => $product->pivot->quantity,
                            'unit_price' => $product->pivot->unit_price,
                            'source_delivery_id' => $delivery->id,
                        ]);
                    } else {
                        \App\Models\StockBatch::create([
                            'store_id' => null,
                            'reseller_id' => $reseller,
                            'product_id' => $product->id,
                            'quantity' => $product->pivot->quantity,
                            'unit_price' => $product->pivot->unit_price,
                            'source_delivery_id' => $delivery->id,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('resellers.show', $reseller)->with('success', 'Delivery updated successfully.');
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
}
