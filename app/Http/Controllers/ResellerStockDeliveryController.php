<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Reseller;
use App\Models\ResellerStockDelivery;
use App\Models\ResellerStockBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResellerStockDeliveryController extends Controller
{
    public function create(Reseller $reseller)
    {
        $products = Product::where('is_resalable', true)->get();
        return view('resellers.deliveries.create', compact('reseller', 'products'));
    }

    public function show(Reseller $reseller, ResellerStockDelivery $delivery)
    {
        if ($delivery->reseller_id !== $reseller->id) {
            abort(404);
        }
        $delivery->load('products', 'reseller');
        return view('resellers.deliveries.show', compact('reseller', 'delivery'));
    }

    public function edit(Reseller $reseller, ResellerStockDelivery $delivery)
    {
        if ($delivery->reseller_id !== $reseller->id) {
            abort(404);
        }
        $delivery->load('products');
        return view('resellers.deliveries.edit', compact('reseller', 'delivery'));
    }

    public function store(Request $request, Reseller $reseller)
    {
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

        DB::transaction(function () use ($reseller, $productsToDeliver, $validated) {
            $delivery = ResellerStockDelivery::create([
                'reseller_id' => $reseller->id,
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

    public function update(Request $request, Reseller $reseller, ResellerStockDelivery $delivery)
    {
        if ($delivery->reseller_id !== $reseller->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ResellerStockDelivery::STATUS_OPTIONS))],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($delivery, $reseller, $validated) {
            $oldStatus = $delivery->status;

            $delivery->update($validated);

            // Si on passe en statut "shipped" pour la première fois, on enregistre les lots
            if ($oldStatus !== 'shipped' && $delivery->status === 'shipped') {
                foreach ($delivery->products as $product) {
                    ResellerStockBatch::create([
                        'reseller_id' => $reseller->id,
                        'product_id' => $product->id,
                        'quantity' => $product->pivot->quantity,
                        'unit_price' => $product->pivot->unit_price,
                        'source_delivery_id' => $delivery->id,
                    ]);
                }
            }
        });

        return redirect()->route('resellers.show', $reseller)->with('success', 'Delivery updated successfully.');
    }

}
