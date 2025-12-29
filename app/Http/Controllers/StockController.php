<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Store;
use App\Models\StockBatch;
use App\Models\Reseller;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 100);
        $perPage = in_array($perPage, [25, 50, 100]) ? $perPage : 100;

        // Recherche par EAN ou nom avec Meilisearch
        if ($request->filled('q')) {
            $q = $request->q;

            // Utiliser Meilisearch pour la recherche
            $searchResults = Product::search($q)->get();
            $productIds = $searchResults->pluck('id');

            if ($productIds->isNotEmpty()) {
                // Récupérer les produits avec leurs relations, dans l'ordre de pertinence
                $query = Product::with('brand')
                    ->whereIn('id', $productIds)
                    ->orderByRaw('FIELD(id, ' . $productIds->implode(',') . ')');

                // Filtre par marque
                if ($request->filled('brand_id')) {
                    if ($request->brand_id === 'none') {
                        $query->whereNull('brand_id');
                    } else {
                        $query->where('brand_id', $request->brand_id);
                    }
                }

                $products = $query->paginate($perPage)->withQueryString();
            } else {
                // Aucun résultat trouvé
                $products = Product::whereRaw('1 = 0')->paginate($perPage)->withQueryString();
            }
        } else {
            // Sans recherche, afficher tous les produits
            $query = Product::with('brand');

            // Filtre par marque
            if ($request->filled('brand_id')) {
                if ($request->brand_id === 'none') {
                    $query->whereNull('brand_id');
                } else {
                    $query->where('brand_id', $request->brand_id);
                }
            }

            $products = $query->paginate($perPage)->withQueryString();
        }

        $brands = Brand::orderBy('name')->get();

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

        return view('stocks.index', compact('products', 'brands', 'shops', 'stocks', 'pivotAlerts'));
    }

    public function reseller(Request $request)
    {
        $perPage = $request->input('perPage', 100);
        $perPage = in_array($perPage, [25, 50, 100]) ? $perPage : 100;

        // Récupérer tous les resellers (classiques + shops)
        $resellers = Reseller::allWithShops();

        // Reseller sélectionné
        $selectedResellerId = $request->input('reseller_id');
        $selectedReseller = null;
        $products = collect();
        $stock = collect();

        if ($selectedResellerId) {
            // Trouver le reseller sélectionné
            $selectedReseller = $resellers->first(function ($r) use ($selectedResellerId) {
                return (string)$r->id === $selectedResellerId;
            });

            if ($selectedReseller) {
                // Récupérer le stock du reseller
                if (str_starts_with($selectedResellerId, 'shop-')) {
                    $shopId = (int) str_replace('shop-', '', $selectedResellerId);
                    $shop = Store::find($shopId);
                    $stock = $shop ? $shop->getCurrentStock() : collect();
                } else {
                    $reseller = Reseller::find($selectedResellerId);
                    $stock = $reseller ? $reseller->getCurrentStock() : collect();
                }

                // Filtrer les produits avec stock > 0
                $productIdsWithStock = $stock->filter(fn($qty) => $qty > 0)->keys();

                // Recherche par EAN ou nom
                if ($request->filled('q')) {
                    $q = $request->q;
                    $searchResults = Product::search($q)->get();
                    $searchIds = $searchResults->pluck('id');

                    // Intersection avec les produits en stock
                    $finalIds = $productIdsWithStock->intersect($searchIds);

                    if ($finalIds->isNotEmpty()) {
                        $query = Product::with('brand')
                            ->whereIn('id', $finalIds)
                            ->orderByRaw('FIELD(id, ' . $finalIds->implode(',') . ')');

                        // Filtre par marque
                        if ($request->filled('brand_id')) {
                            if ($request->brand_id === 'none') {
                                $query->whereNull('brand_id');
                            } else {
                                $query->where('brand_id', $request->brand_id);
                            }
                        }

                        $products = $query->paginate($perPage)->withQueryString();
                    } else {
                        $products = Product::whereRaw('1 = 0')->paginate($perPage)->withQueryString();
                    }
                } else {
                    // Sans recherche, afficher tous les produits en stock
                    $query = Product::with('brand')->whereIn('id', $productIdsWithStock);

                    // Filtre par marque
                    if ($request->filled('brand_id')) {
                        if ($request->brand_id === 'none') {
                            $query->whereNull('brand_id');
                        } else {
                            $query->where('brand_id', $request->brand_id);
                        }
                    }

                    $products = $query->orderBy('name')->paginate($perPage)->withQueryString();
                }
            }
        }

        $brands = Brand::orderBy('name')->get();

        return view('stocks.reseller', compact('resellers', 'selectedReseller', 'selectedResellerId', 'products', 'stock', 'brands'));
    }
}
