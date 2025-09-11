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
        $query = Product::query();

        // Recherche par EAN ou nom
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('ean', 'like', "%{$q}%")
                    ->orWhere('name->fr', 'like', "%{$q}%")
                    ->orWhere('name->en', 'like', "%{$q}%");
            });
        }

        $products = $query->paginate(15)->withQueryString();
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
