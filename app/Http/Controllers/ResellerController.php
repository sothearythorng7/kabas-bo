<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Reseller;
use App\Models\ResellerStockReturn;
use App\Models\ResellerStockDelivery;
use App\Models\ResellerSalesReport;
use App\Models\ResellerInvoice;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ResellerSalesReportAnomaly;
use App\Models\ResellerProductPrice;
use App\Models\Store;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ResellerController extends Controller
{
    public function index()
    {
        // Récupération des resellers et shops
        $allResellers = Reseller::allWithShops();

        // Récupérer les comptages de livraisons par statut pour tous les resellers
        $deliveryCountsByReseller = ResellerStockDelivery::select('reseller_id', 'status', DB::raw('COUNT(*) as count'))
            ->whereNotNull('reseller_id')
            ->groupBy('reseller_id', 'status')
            ->get()
            ->groupBy('reseller_id')
            ->map(function ($items) {
                return $items->pluck('count', 'status')->toArray();
            });

        // Récupérer les comptages de livraisons par statut pour les shops
        $deliveryCountsByStore = ResellerStockDelivery::select('store_id', 'status', DB::raw('COUNT(*) as count'))
            ->whereNotNull('store_id')
            ->groupBy('store_id', 'status')
            ->get()
            ->groupBy('store_id')
            ->map(function ($items) {
                return $items->pluck('count', 'status')->toArray();
            });

        // Ajouter les comptages à chaque reseller
        $allResellers = $allResellers->map(function ($reseller) use ($deliveryCountsByReseller, $deliveryCountsByStore) {
            if (property_exists($reseller, 'is_shop') && $reseller->is_shop) {
                $storeId = $reseller->store->id;
                $reseller->delivery_counts = $deliveryCountsByStore[$storeId] ?? [];
            } else {
                $reseller->delivery_counts = $deliveryCountsByReseller[$reseller->id] ?? [];
            }
            return $reseller;
        });

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

        // Passer les statuts disponibles à la vue
        $deliveryStatuses = ResellerStockDelivery::STATUS_OPTIONS;

        return view('resellers.index', compact('resellers', 'deliveryStatuses'));
    }

    public function overview()
    {
        // Récupérer tous les revendeurs (y compris shops)
        $allResellers = Reseller::allWithShops();

        // === LIVRAISONS ===
        $pendingDeliveries = ResellerStockDelivery::whereIn('status', ['draft', 'ready_to_ship'])
            ->with(['reseller', 'store', 'products'])
            ->latest()
            ->get();

        $shippedDeliveries = ResellerStockDelivery::where('status', 'shipped')
            ->with(['reseller', 'store', 'products'])
            ->latest()
            ->limit(20)
            ->get();

        // === RAPPORTS DE VENTES ===
        // Rapports en attente (pas encore de facture)
        $pendingReports = ResellerSalesReport::doesntHave('invoice')
            ->with(['reseller', 'store', 'items'])
            ->latest()
            ->get();

        // Rapports traités (avec facture)
        $processedReports = ResellerSalesReport::has('invoice')
            ->with(['reseller', 'store', 'items', 'invoice'])
            ->latest()
            ->limit(20)
            ->get();

        // === FACTURES ===
        // Factures de sales reports en attente de paiement
        $unpaidInvoices = ResellerInvoice::whereIn('status', ['unpaid', 'partially_paid'])
            ->whereNotNull('sales_report_id')
            ->with(['reseller', 'store', 'salesReport', 'payments'])
            ->latest()
            ->get();

        // Factures payées (récentes)
        $paidInvoices = ResellerInvoice::where('status', 'paid')
            ->with(['reseller', 'store', 'resellerStockDelivery', 'salesReport'])
            ->latest()
            ->limit(20)
            ->get();

        // === STATISTIQUES ===
        $stats = [
            'total_resellers' => $allResellers->count(),
            'pending_deliveries' => $pendingDeliveries->count(),
            'shipped_deliveries' => $shippedDeliveries->count(),
            'pending_reports' => $pendingReports->count(),
            'unpaid_invoices' => $unpaidInvoices->count(),
            'unpaid_amount' => $unpaidInvoices->sum(function ($invoice) {
                $paid = $invoice->payments->sum('amount');
                return $invoice->total_amount - $paid;
            }),
        ];

        $paymentMethods = \App\Models\FinancialPaymentMethod::all();

        return view('resellers.overview', compact(
            'allResellers',
            'pendingDeliveries',
            'shippedDeliveries',
            'pendingReports',
            'processedReports',
            'unpaidInvoices',
            'paidInvoices',
            'stats',
            'paymentMethods'
        ));
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
            'address' => null,
            'address2' => null,
            'city' => null,
            'postal_code' => null,
            'country' => null,
            'phone' => null,
            'email' => null,
            'tax_id' => null,
        ];

        $stock = $shop->getCurrentStock();

        // Récupérer les marques disponibles pour les produits en stock
        $brands = Brand::whereHas('products', function ($q) use ($stock) {
            $q->whereIn('id', $stock->keys());
        })->orderBy('name')->get();

        // Construire la requête de base
        $productsQuery = Product::whereIn('id', $stock->keys())->with('brand');

        // Filtre par marque
        if ($request->filled('brand_id')) {
            if ($request->brand_id === 'none') {
                $productsQuery->whereNull('brand_id');
            } else {
                $productsQuery->where('brand_id', $request->brand_id);
            }
        }

        // Recherche avec Meilisearch si query présente
        if ($request->filled('q')) {
            $q = $request->q;
            $searchResults = Product::search($q)->get();
            $productIds = $searchResults->pluck('id')->intersect($stock->keys());

            // Appliquer aussi le filtre marque sur les résultats de recherche
            if ($request->filled('brand_id')) {
                if ($request->brand_id === 'none') {
                    $productIds = $productIds->intersect(
                        Product::whereIn('id', $productIds)->whereNull('brand_id')->pluck('id')
                    );
                } else {
                    $productIds = $productIds->intersect(
                        Product::whereIn('id', $productIds)->where('brand_id', $request->brand_id)->pluck('id')
                    );
                }
            }

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
            $products = $productsQuery
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
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
        })->with(['product', 'report', 'resolvedBy'])->latest()->paginate(10);

        $pendingDisputesCount = \App\Models\ResellerSalesReportAnomaly::whereHas('report', function($q) use($shopId){
            $q->where('store_id', $shopId);
        })->pending()->count();

        // Récupérer les retours pour ce shop
        $returns = ResellerStockReturn::where('store_id', $shopId)
            ->with(['items.product', 'destinationStore'])
            ->latest()
            ->paginate(10);

        return view('resellers.show', compact(
            'reseller','products','deliveries','stock','salesReports','anomalies','alertStocks','returns','brands','pendingDisputesCount'
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

    // Récupérer les marques disponibles pour les produits en stock
    $brands = Brand::whereHas('products', function ($q) use ($stock) {
        $q->whereIn('id', $stock->keys());
    })->orderBy('name')->get();

    // Construire la requête de base
    $productsQuery = Product::whereIn('id', $stock->keys())->with('brand');

    // Filtre par marque
    if ($request->filled('brand_id')) {
        if ($request->brand_id === 'none') {
            $productsQuery->whereNull('brand_id');
        } else {
            $productsQuery->where('brand_id', $request->brand_id);
        }
    }

    // Recherche avec Meilisearch si query présente
    if ($request->filled('q')) {
        $q = $request->q;
        $searchResults = Product::search($q)->get();
        $productIds = $searchResults->pluck('id')->intersect($stock->keys());

        // Appliquer aussi le filtre marque sur les résultats de recherche
        if ($request->filled('brand_id')) {
            if ($request->brand_id === 'none') {
                $productIds = $productIds->intersect(
                    Product::whereIn('id', $productIds)->whereNull('brand_id')->pluck('id')
                );
            } else {
                $productIds = $productIds->intersect(
                    Product::whereIn('id', $productIds)->where('brand_id', $request->brand_id)->pluck('id')
                );
            }
        }

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
        $products = $productsQuery
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }

    // Récupérer les alertes de stock (pour resellers, on n'a pas de store_id fixe)
    $alertStocks = collect();

    $deliveries = $reseller->deliveries()
        ->with(['products','invoice'])
        ->latest()
        ->paginate(10);

    $salesReports = $reseller->type === 'consignment'
        ? $reseller->reports()->with('items.product', 'invoice.payments')->latest()->paginate(10)
        : collect();

    $anomalies = ResellerSalesReportAnomaly::whereHas('report', function($q) use($reseller){
        $q->where('reseller_id', $reseller->id);
    })->with(['product', 'report', 'resolvedBy'])->latest()->paginate(10);

    $pendingDisputesCount = ResellerSalesReportAnomaly::whereHas('report', function($q) use($reseller){
        $q->where('reseller_id', $reseller->id);
    })->pending()->count();

    // Récupérer les retours pour ce reseller
    $returns = ResellerStockReturn::where('reseller_id', $reseller->id)
        ->with(['items.product', 'destinationStore'])
        ->latest()
        ->paginate(10);

    // Récupérer les prix B2B personnalisés pour ce revendeur
    $resellerPrices = ResellerProductPrice::where('reseller_id', $reseller->id)
        ->pluck('price', 'product_id')
        ->toArray();

    return view('resellers.show', compact(
        'reseller','products','deliveries','stock','salesReports','anomalies','alertStocks','returns','brands','resellerPrices','pendingDisputesCount'
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

    /**
     * Update B2B price for a specific product for this reseller
     */
    public function updateProductPrice(Request $request, $id)
    {
        // Vérifier que ce n'est pas un shop
        if (str_starts_with($id, 'shop-')) {
            return redirect()->back()->withErrors('Les tarifs B2B personnalisés ne sont pas disponibles pour les shops.');
        }

        $reseller = Reseller::findOrFail($id);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'price' => 'required|numeric|min:0',
        ]);

        ResellerProductPrice::setPriceFor($reseller->id, $request->product_id, $request->price);

        return redirect()->route('resellers.show', $id . '?tab=products')
            ->with('success', __('messages.resellers.price_updated'));
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

    /**
     * Update reseller billing/contact information
     */
    public function updateInfo(Request $request, Reseller $reseller)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:100',
        ]);

        $reseller->update($data);

        return redirect()->route('resellers.show', ['reseller' => $reseller->id, 'tab' => 'info'])
            ->with('success', __('messages.resellers.info_updated'));
    }
}
