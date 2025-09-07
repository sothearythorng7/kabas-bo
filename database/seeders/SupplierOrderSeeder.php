<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Store;
use App\Models\SupplierOrder;
use App\Models\StockLot;

class SupplierOrderSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::all();
        $stores    = Store::all();
        $products  = Product::all();

        if ($suppliers->isEmpty() || $stores->isEmpty() || $products->isEmpty()) {
            $this->command->error('Suppliers, Stores, or Products are missing. Seed them first.');
            return;
        }

        foreach ($suppliers as $supplier) {
            // Lier quelques produits au fournisseur
            $supplierProducts = $products->random(rand(5, 10));

            foreach ($supplierProducts as $product) {
                $purchasePrice = rand(50, 200);

                // Attacher le produit au fournisseur avec un prix d'achat
                $supplier->products()->syncWithoutDetaching([
                    $product->id => ['purchase_price' => $purchasePrice]
                ]);

                // Générer 1 à 3 commandes fournisseur
                for ($c = 0; $c < rand(1, 3); $c++) {
                    $store = $stores->random();

                    $qtyOrdered = rand(5, 30);

                    $order = SupplierOrder::create([
                        'supplier_id' => $supplier->id,
                        'destination_store_id' => $store->id,
                        'status' => 'received',
                    ]);

                    // Attacher le produit à la commande
                    $order->products()->attach($product->id, [
                        'purchase_price'    => $purchasePrice,
                        'sale_price'        => $product->price,
                        'quantity_ordered'  => $qtyOrdered,
                        'quantity_received' => $qtyOrdered,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);

                    // Générer des lots pour ce produit
                    $remaining = $qtyOrdered;
                    while ($remaining > 0) {
                        $lotQty = rand(1, min(10, $remaining));
                        StockLot::create([
                            'product_id'        => $product->id,
                            'store_id'          => $store->id,
                            'supplier_id'       => $supplier->id,
                            'supplier_order_id' => $order->id,
                            'purchase_price'    => $purchasePrice,
                            'quantity'          => $lotQty,
                            'quantity_remaining'=> $lotQty,
                            'batch_number'      => null,
                            'expiry_date'       => null,
                        ]);
                        $remaining -= $lotQty;
                    }

                    // Stock d'alerte aléatoire
                    $alertQty = rand(1, max(1, $qtyOrdered));
                    $store->products()->syncWithoutDetaching([
                        $product->id => ['alert_stock_quantity' => $alertQty]
                    ]);
                }
            }
        }
    }
}
