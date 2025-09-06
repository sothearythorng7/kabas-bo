<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Store;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['stores' => function ($q) {
            $q->withPivot('stock_quantity');
        }]);

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

        return view('stocks.index', compact('products', 'shops'));
    }
}
