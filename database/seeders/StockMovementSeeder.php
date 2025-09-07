<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\Product;
use App\Models\Store;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();
        $products = Product::all();

        for ($i = 0; $i < 10; $i++) { // 10 mouvements
            $from = $stores->random();
            $to = $stores->where('id', '!=', $from->id)->random();

            $movement = StockMovement::create([
                'from_store_id' => $from->id,
                'to_store_id' => $to->id,
                'note' => 'Movement #' . ($i + 1),
                'user_id' => 1,
                'status' => StockMovement::STATUS_VALIDATED,
            ]);

            foreach ($products->random(rand(2, 5)) as $product) {
                $qty = rand(1, 20);

                $movement->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                ]);

                // Décrémenter le stock source
                $from->products()->where('product_id', $product->id)->decrement('stock_quantity', $qty);

                // Créer un lot simulé pour le destinataire
                $to->products()->where('product_id', $product->id)->increment('stock_quantity', $qty);
            }
        }
    }
}
