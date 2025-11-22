<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ResellerSalesReportAnomaly;
use App\Models\Store;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ResellerController extends Controller
{
    public function index()
    {
        // Récupération des resellers et shops
        $allResellers = Reseller::allWithShops();

        // Pagination manuelle
        $page = request()->get('page', 1);
        $perPage = 15;
        $items = $allResellers->forPage($page, $perPage);

        $resellers = new LengthAwarePaginator(
            $items,
            $allResellers->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('resellers.index', compact('resellers'));
    }

    public function create()
    {
        return view('resellers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:buyer,consignment',
        ]);

        $reseller = Reseller::create($data);

        return redirect()->route('resellers.show', $reseller)->with('success', 'Reseller created.');
    }

    /*
    public function show($id)
    {
        // Cas particulier pour un shop
        if (str_starts_with($id, 'shop-')) {
            $shopId = (int) str_replace('shop-', '', $id);
            $shop = Store::findOrFail($shopId);

            $reseller = (object)[
                'id' => $id,
                'name' => $shop->name,
                'type' => 'consignment',
                'contacts' => collect(),
                'is_shop' => true,
                'store' => $shop,
            ];

            $stock = $shop->getCurrentStock();

            $products = Product::whereIn('id', $stock->keys())
                ->with('brand')
                ->orderBy('name')
                ->paginate(20);

            // Récupération des livraisons pour ce shop
            $deliveries = \App\Models\ResellerStockDelivery::where('store_id', $shopId)
                ->with(['products','reseller'])
                ->latest()
                ->paginate(10);

            $salesReports = collect(); // Pas de rapports pour les shops
            $anomalies = collect(); // Pas d’anomalies pour les shops

            return view('resellers.show', compact(
                'reseller','products','deliveries','stock','salesReports','anomalies'
            ));
        }

        // Reseller classique
        $reseller = Reseller::with([
            'contacts',
            'deliveries.products',
            'deliveries.invoice',
            'reports.items.product',
        ])->findOrFail($id);

        $stock = $reseller->getCurrentStock();

        $products = Product::whereIn('id', $stock->keys())
            ->with('brand')
            ->orderBy('name')
            ->paginate(20);

        $deliveries = $reseller->deliveries()
            ->with(['products','invoice'])
            ->latest()
            ->paginate(10);

        $salesReports = $reseller->type === 'consignment'
            ? $reseller->reports()->with('items.product')->latest()->paginate(10)
            : collect();

        $anomalies = ResellerSalesReportAnomaly::whereHas('report', function($q) use($reseller){
            $q->where('reseller_id', $reseller->id);
        })->latest()->paginate(10);

        return view('resellers.show', compact(
            'reseller','products','deliveries','stock','salesReports','anomalies'
        ));
    }
    */

public function show(Request $request, $id)
{
    // Cas particulier pour un shop
    if (str_starts_with($id, 'shop-')) {
        $shopId = (int) str_replace('shop-', '', $id);
        $shop = Store::findOrFail($shopId);

        $reseller = (object)[
            'id' => $id,
            'name' => $shop->name,
            'type' => 'consignment',
            'contacts' => collect(),
            'is_shop' => true,
            'store' => $shop,
        ];

        $stock = $shop->getCurrentStock();

        // Recherche avec Meilisearch si query présente
        if ($request->filled('q')) {
            $q = $request->q;
            $searchResults = Product::search($q)->get();
            $productIds = $searchResults->pluck('id')->intersect($stock->keys());

            if ($productIds->isNotEmpty()) {
                $products = Product::whereIn('id', $productIds)
                    ->with('brand')
                    ->orderByRaw('FIELD(id, ' . $productIds->implode(',') . ')')
                    ->paginate(20)
                    ->withQueryString();
            } else {
                $products = Product::whereRaw('1 = 0')->paginate(20)->withQueryString();
            }
        } else {
            $products = Product::whereIn('id', $stock->keys())
                ->with('brand')
                ->orderBy('name')
                ->paginate(20);
        }

        // Récupérer les alertes de stock depuis product_store
        $alertStocks = \Illuminate\Support\Facades\DB::table('product_store')
            ->where('store_id', $shopId)
            ->whereIn('product_id', $products->pluck('id'))
            ->pluck('alert_stock_quantity', 'product_id');

        $deliveries = \App\Models\ResellerStockDelivery::where('store_id', $shopId)
            ->with(['products','reseller'])
            ->latest()
            ->paginate(10);

        // Ici : on récupère les sales reports du shop
        $salesReports = \App\Models\ResellerSalesReport::where('store_id', $shopId)
            ->with('items.product', 'invoice.payments')
            ->latest()
            ->paginate(10);

        $anomalies = \App\Models\ResellerSalesReportAnomaly::whereHas('report', function($q) use($shopId){
            $q->where('store_id', $shopId);
        })->latest()->paginate(10);

        return view('resellers.show', compact(
            'reseller','products','deliveries','stock','salesReports','anomalies','alertStocks'
        ));
    }

    // Reseller classique
    $reseller = Reseller::with([
        'contacts',
        'deliveries.products',
        'deliveries.invoice',
        'reports.items.product',
    ])->findOrFail($id);

    $stock = $reseller->getCurrentStock();

    // Recherche avec Meilisearch si query présente
    if ($request->filled('q')) {
        $q = $request->q;
        $searchResults = Product::search($q)->get();
        $productIds = $searchResults->pluck('id')->intersect($stock->keys());

        if ($productIds->isNotEmpty()) {
            $products = Product::whereIn('id', $productIds)
                ->with('brand')
                ->orderByRaw('FIELD(id, ' . $productIds->implode(',') . ')')
                ->paginate(20)
                ->withQueryString();
        } else {
            $products = Product::whereRaw('1 = 0')->paginate(20)->withQueryString();
        }
    } else {
        $products = Product::whereIn('id', $stock->keys())
            ->with('brand')
            ->orderBy('name')
            ->paginate(20);
    }

    // Récupérer les alertes de stock (pour resellers, on n'a pas de store_id fixe)
    $alertStocks = collect();

    $deliveries = $reseller->deliveries()
        ->with(['products','invoice'])
        ->latest()
        ->paginate(10);

    $salesReports = $reseller->type === 'consignment'
        ? $reseller->reports()->with('items.product')->latest()->paginate(10)
        : collect();

    $anomalies = ResellerSalesReportAnomaly::whereHas('report', function($q) use($reseller){
        $q->where('reseller_id', $reseller->id);
    })->latest()->paginate(10);

    return view('resellers.show', compact(
        'reseller','products','deliveries','stock','salesReports','anomalies','alertStocks'
    ));
}


    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'new_stock' => 'required|integer|min:0',
            'alert_stock' => 'nullable|integer|min:0',
            'note' => 'nullable|string',
        ]);

        // Vérifier si c'est un shop
        if (str_starts_with($id, 'shop-')) {
            $shopId = (int) str_replace('shop-', '', $id);
            $shop = Store::findOrFail($shopId);
            $product = Product::findOrFail($request->product_id);

            // Calculer le stock actuel
            $currentStock = $shop->getCurrentStock()[$product->id] ?? 0;
            $difference = $request->new_stock - $currentStock;

            if ($difference != 0) {
                // Créer un StockMovement d'ajustement
                DB::beginTransaction();
                try {
                    $movement = \App\Models\StockMovement::create([
                        'type' => \App\Models\StockMovement::TYPE_ADJUSTMENT,
                        'from_store_id' => $shopId,
                        'to_store_id' => $shopId,
                        'note' => $request->note ?? 'Ajustement de stock manuel',
                        'user_id' => auth()->id(),
                        'status' => \App\Models\StockMovement::STATUS_VALIDATED,
                    ]);

                    $movement->items()->create([
                        'product_id' => $product->id,
                        'quantity' => abs($difference),
                    ]);

                    // Créer un StockBatch pour l'ajustement
                    \App\Models\StockBatch::create([
                        'product_id' => $product->id,
                        'store_id' => $shopId,
                        'quantity' => $difference,
                        'unit_price' => 0,
                    ]);

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    return redirect()->back()->withErrors('Erreur lors de l\'ajustement: ' . $e->getMessage());
                }
            }

            // Mettre à jour ou créer le seuil d'alerte dans product_store
            if ($request->filled('alert_stock')) {
                DB::table('product_store')->updateOrInsert(
                    [
                        'product_id' => $product->id,
                        'store_id' => $shopId,
                    ],
                    [
                        'alert_stock_quantity' => $request->alert_stock,
                    ]
                );
            }

            return redirect()->route('resellers.show', $id . '?tab=products')
                ->with('success', 'Stock mis à jour avec succès');
        }

        return redirect()->back()->withErrors('Mise à jour de stock non supportée pour ce type de revendeur');
    }

    public function edit(Reseller $reseller)
    {
        return view('resellers.edit', compact('reseller'));
    }

    public function update(Request $request, Reseller $reseller)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:buyer,consignment',
        ]);

        $reseller->update($data);

        return redirect()->route('resellers.show', $reseller)->with('success', 'Reseller updated.');
    }

    public function destroy(Reseller $reseller)
    {
        $reseller->delete();
        return redirect()->route('resellers.index')->with('success', 'Reseller deleted.');
    }
}
