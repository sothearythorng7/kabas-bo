<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class StockValueController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['lots.store']);

        // Filtre recherche
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('ean', 'like', "%{$search}%")
                  ->orWhereJsonContains('name->'.app()->getLocale(), $search);
            });
        }

        // Pagination
        $products = $query->paginate(15)->withQueryString();

        // Total global de tous les stocks
        $totalValue = $products->reduce(function($carry, $product) {
            $productValue = $product->lots->sum(function($lot) {
                return $lot->quantity_remaining * $lot->purchase_price;
            });
            return $carry + $productValue;
        }, 0);

        return view('accounting.stock_value', compact('products', 'totalValue'));
    }

    public function lots(Product $product)
    {
        $lots = $product->lots()->with('store')->where('quantity_remaining', '>', 0)->get();
        return view('accounting.stock_value_lot', compact('lots', 'product'));
    }
}
