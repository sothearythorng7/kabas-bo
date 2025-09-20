<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Store;
use App\Models\SupplierOrder;
use App\Models\StockBatch;

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
            // Si supplier = consignement, on retire waiting_invoice
            $statusOptions = $supplier->type === 'consignment'
                ? ['pending', 'waiting_reception']
                : ['pending', 'waiting_reception', 'waiting_invoice'];

            $supplierProducts = $products->random(rand(5, 10));

            foreach ($supplierProducts as $product) {
                $purchasePrice = rand(50, 200);
                $supplier->products()->syncWithoutDetaching([
                    $product->id => ['purchase_price' => $purchasePrice]
                ]);

                for ($c = 0; $c < rand(1, 3); $c++) {
                    $store = $stores->random();
                    $qtyOrdered = rand(5, 30);
                    $status = $statusOptions[array_rand($statusOptions)];

                    // Correction : quantité reçue logique
                    $qtyReceived = $status === 'waiting_invoice' ? $qtyOrdered : 0;

                    $order = SupplierOrder::create([
                        'supplier_id'          => $supplier->id,
                        'destination_store_id' => $store->id,
                        'status'               => $status,
                    ]);

                    $order->products()->attach($product->id, [
                        'purchase_price'    => $purchasePrice,
                        'sale_price'        => $product->price,
                        'invoice_price'     => $purchasePrice,
                        'quantity_ordered'  => $qtyOrdered,
                        'quantity_received' => $qtyReceived,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);

                    // Générer des lots uniquement si des articles sont reçus
                    $remaining = $qtyReceived;
                    while ($remaining > 0) {
                        $lotQty = rand(1, min(10, $remaining));
                        StockBatch::create([
                            'product_id'         => $product->id,
                            'store_id'           => $store->id,
                            'reseller_id'        => null,
                            'quantity'           => $lotQty,
                            'unit_price'         => $purchasePrice,
                            'source_delivery_id' => null,
                        ]);
                        $remaining -= $lotQty;
                    }

                    $alertQty = rand(1, max(1, $qtyOrdered));
                    $store->products()->syncWithoutDetaching([
                        $product->id => ['alert_stock_quantity' => $alertQty]
                    ]);
                }
            }
        }
    }
}
