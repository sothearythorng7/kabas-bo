<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;
use App\Models\StockBatch;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        // Recherche par EAN ou nom avec Meilisearch
        if ($request->filled('q')) {
            $q = $request->q;

            // Utiliser Meilisearch pour la recherche
            $searchResults = Product::search($q)->get();
            $productIds = $searchResults->pluck('id');

            if ($productIds->isNotEmpty()) {
                // Récupérer les produits avec leurs relations, dans l'ordre de pertinence
                $products = Product::whereIn('id', $productIds)
                    ->orderByRaw('FIELD(id, ' . $productIds->implode(',') . ')')
                    ->paginate(15)
                    ->withQueryString();
            } else {
                // Aucun résultat trouvé
                $products = Product::whereRaw('1 = 0')->paginate(15)->withQueryString();
            }
        } else {
            // Sans recherche, afficher tous les produits
            $products = Product::query()->paginate(15)->withQueryString();
        }

        $shops = Store::all();

        // Stock par batch (sum des quantités)
        $stocks = StockBatch::selectRaw('store_id, product_id, SUM(quantity) as stock_quantity')
            ->whereIn('product_id', $products->pluck('id'))
            ->groupBy('store_id', 'product_id')
            ->get()
            ->groupBy('product_id');

        // Alertes depuis product_store (clé store_id => alert)
        $pivotAlerts = DB::table('product_store')
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                return $items->pluck('alert_stock_quantity', 'store_id');
            });

        return view('stocks.index', compact('products', 'shops', 'stocks', 'pivotAlerts'));
    }
}
