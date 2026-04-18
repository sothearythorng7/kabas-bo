<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\FinancialPaymentMethod;
use App\Models\Store;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\WebsiteOrder;
use App\Models\Reseller;
use App\Models\ResellerSalesReport;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SupplierSalesExport;
use Carbon\Carbon;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        // Exclure les fournisseurs de matières premières (ils sont gérés dans /factory/suppliers)
        $suppliers = Supplier::with('contacts')
            ->productSuppliers()
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->only('search'));
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'address'    => 'nullable|string',
            'type'       => 'required|in:buyer,consignment',
            'last_name'  => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email'      => 'required|email',
            'phone'      => 'required|string|max:50',
        ]);

        $supplier = Supplier::create($request->only(['name', 'address', 'type']));

        $supplier->contacts()->create($request->only(['last_name','first_name','email','phone']));

        return redirect()->route('suppliers.index')->with('success', 'Supplier created');
    }

public function edit(Supplier $supplier, Request $request)
{
    $supplier->load(['contacts', 'products.stores', 'products.brand', 'saleReports.store']);

    // Produits paginés avec nombre par page configurable
    $perPage = (int) $request->get('per_page', 50);
    $perPage = in_array($perPage, [50, 100, 200]) ? $perPage : 50;

    // Si recherche textuelle, utiliser Meilisearch via Scout
    if ($request->filled('product_search')) {
        $supplierProductIds = $supplier->products()->pluck('products.id')->toArray();

        $searchQuery = Product::search($request->product_search)
            ->query(function ($builder) use ($supplierProductIds) {
                $builder->whereIn('id', $supplierProductIds)
                    ->with(['stores', 'brand']);
            });

        $products = $searchQuery->paginate($perPage)->appends($request->only(['per_page', 'product_search']));
    } else {
        $products = $supplier->products()->with(['stores', 'brand'])->paginate($perPage)->appends($request->only('per_page'));
    }

    // Commandes
    $orders = collect();
    $totalUnpaidAmount = 0;
    $unpaidOrdersCount = 0;

    $paymentMethods = FinancialPaymentMethod::all();

    // Toutes les commandes, quel que soit le type
    $query = $supplier->supplierOrders()->latest();

    if ($request->filled('status')) {
        switch ($request->status) {
            case 'pending':
            case 'waiting_reception':
                $query->where('status', $request->status);
                break;
            case 'received_unpaid':
                if ($supplier->isBuyer()) {
                    $query->where('status', 'received')->where('is_paid', false);
                }
                break;
            case 'received_paid':
                if ($supplier->isBuyer()) {
                    $query->where('status', 'received')->where('is_paid', true);
                }
                break;
        }
    }

    $orders = $query->paginate(10)->appends($request->only('status'));

    // Montant total des factures non payées et nombre de commandes uniquement pour les buyers
    if ($supplier->isBuyer()) {
        $unpaidOrdersQuery = $supplier->supplierOrders()
            ->where('status', 'received')
            ->where('is_paid', false)
            ->with('products');

        $totalUnpaidAmount = $unpaidOrdersQuery->get()->sum(fn($order) =>
            $order->products->sum(fn($p) => ($p->pivot->invoice_price ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_received ?? 0))
        );

        $unpaidOrdersCount = $unpaidOrdersQuery->count();
    }

    // Ventes des produits pour les buyers
    $salesStores = collect();
    $salesData = null;
    $salesTotals = ['total_quantity' => 0, 'total_revenue' => 0];
    $salesStartDate = now()->subDays(30)->format('Y-m-d');
    $salesEndDate = now()->format('Y-m-d');
    $salesStoreId = null;

    if ($supplier->isBuyer()) {
        $salesStores = Store::where('type', 'shop')->get();

        // Filtres avec valeurs par défaut (30 derniers jours)
        $salesStartDate = $request->get('sales_start_date', now()->subDays(30)->format('Y-m-d'));
        $salesEndDate = $request->get('sales_end_date', now()->format('Y-m-d'));
        $salesStoreId = $request->get('sales_store_id');

        // IDs des produits du fournisseur
        $productIds = $supplier->products()->pluck('products.id')->toArray();

        if (!empty($productIds)) {
            // Requête groupée par produit
            $salesQuery = SaleItem::whereIn('product_id', $productIds)
                ->whereNull('exchanged_at')
                ->whereHas('sale', function ($q) use ($salesStartDate, $salesEndDate, $salesStoreId) {
                    $q->whereDate('created_at', '>=', $salesStartDate)
                      ->whereDate('created_at', '<=', $salesEndDate);
                    if ($salesStoreId) {
                        $q->where('store_id', $salesStoreId);
                    }
                });

            $salesData = (clone $salesQuery)
                ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(quantity * price) as total_revenue')
                ->groupBy('product_id')
                ->with('product')
                ->paginate(50)
                ->appends($request->only(['sales_start_date', 'sales_end_date', 'sales_store_id']));

            $salesTotals = [
                'total_quantity' => (clone $salesQuery)->sum('quantity'),
                'total_revenue' => (clone $salesQuery)->selectRaw('SUM(quantity * price) as total')->value('total') ?? 0,
            ];
        }
    }

    // === Sales Tracking tab (all channels) ===
    $stMonth = $request->get('st_month', now()->format('Y-m'));
    $stChannel = $request->get('st_channel', 'all');
    $stMonthStart = Carbon::parse($stMonth . '-01')->startOfMonth();
    $stMonthEnd = $stMonthStart->copy()->endOfMonth();

    $allProductIds = $supplier->products()->pluck('products.id')->toArray();
    $salesTracking = collect();
    $stStores = Store::where('type', 'shop')->get();
    $stResellers = Reseller::orderBy('name')->get();

    if (!empty($allProductIds)) {
        // POS Sales
        if ($stChannel === 'all' || str_starts_with($stChannel, 'pos')) {
            $posQuery = Sale::with(['store', 'items' => function($q) use ($allProductIds) {
                    $q->whereIn('product_id', $allProductIds)->whereNull('exchanged_at');
                }, 'items.product'])
                ->whereHas('items', function($q) use ($allProductIds) {
                    $q->whereIn('product_id', $allProductIds)->whereNull('exchanged_at');
                })
                ->whereBetween('created_at', [$stMonthStart, $stMonthEnd]);

            if (str_starts_with($stChannel, 'pos_')) {
                $posQuery->where('store_id', (int) str_replace('pos_', '', $stChannel));
            }

            foreach ($posQuery->get() as $sale) {
                $financialUrl = $sale->financial_transaction_id
                    ? route('financial.transactions.show', ['store' => $sale->store_id, 'transaction' => $sale->financial_transaction_id])
                    : null;

                foreach ($sale->items->whereIn('product_id', $allProductIds) as $item) {
                    $salesTracking->push([
                        'date' => $sale->created_at,
                        'channel' => 'pos',
                        'source' => $sale->store->name ?? '-',
                        'product' => $item->product,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->price,
                        'total' => $item->quantity * $item->price,
                        'financial_url' => $financialUrl,
                    ]);
                }
            }
        }

        // Website Orders
        if ($stChannel === 'all' || $stChannel === 'website') {
            $webOrders = WebsiteOrder::with(['items' => function($q) use ($allProductIds) {
                    $q->whereIn('product_id', $allProductIds);
                }, 'items.product'])
                ->whereHas('items', function($q) use ($allProductIds) {
                    $q->whereIn('product_id', $allProductIds);
                })
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$stMonthStart, $stMonthEnd])
                ->get();

            foreach ($webOrders as $order) {
                $orderUrl = route('website-orders.show', $order);

                foreach ($order->items->whereIn('product_id', $allProductIds) as $item) {
                    $salesTracking->push([
                        'date' => $order->paid_at ?? $order->created_at,
                        'channel' => 'website',
                        'source' => $order->order_number,
                        'product' => $item->product,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->quantity * $item->unit_price,
                        'financial_url' => $orderUrl,
                    ]);
                }
            }
        }

        // Reseller Sales
        if ($stChannel === 'all' || str_starts_with($stChannel, 'reseller')) {
            $resellerQuery = ResellerSalesReport::with(['reseller', 'store', 'invoice',
                    'items' => function($q) use ($allProductIds) {
                        $q->whereIn('product_id', $allProductIds);
                    }, 'items.product'])
                ->whereHas('items', function($q) use ($allProductIds) {
                    $q->whereIn('product_id', $allProductIds);
                })
                ->where(function($q) use ($stMonthStart, $stMonthEnd) {
                    $q->whereBetween('created_at', [$stMonthStart, $stMonthEnd]);
                });

            if (str_starts_with($stChannel, 'reseller_')) {
                $resellerQuery->where('reseller_id', (int) str_replace('reseller_', '', $stChannel));
            }

            foreach ($resellerQuery->get() as $report) {
                $invoiceUrl = $report->invoice
                    ? route('reseller-invoices.edit', $report->invoice)
                    : null;

                foreach ($report->items->whereIn('product_id', $allProductIds) as $item) {
                    $salesTracking->push([
                        'date' => $report->created_at,
                        'channel' => 'reseller',
                        'source' => $report->reseller->name ?? '-',
                        'product' => $item->product,
                        'quantity' => $item->quantity_sold,
                        'unit_price' => $item->unit_price,
                        'total' => $item->quantity_sold * $item->unit_price,
                        'financial_url' => $invoiceUrl,
                    ]);
                }
            }
        }
    }

    $salesTracking = $salesTracking->sortByDesc('date')->values();
    $stTotals = [
        'quantity' => $salesTracking->sum('quantity'),
        'total' => $salesTracking->sum('total'),
        'count' => $salesTracking->count(),
    ];

    return view('suppliers.edit', compact(
        'supplier',
        'products',
        'orders',
        'totalUnpaidAmount',
        'unpaidOrdersCount',
        'paymentMethods',
        'salesStores',
        'salesData',
        'salesTotals',
        'salesStartDate',
        'salesEndDate',
        'salesStoreId',
        'salesTracking',
        'stMonth',
        'stChannel',
        'stStores',
        'stResellers',
        'stTotals'
    ));
}



    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string',
        ]);

        $supplier->update($request->only(['name','address']));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted');
    }

    public function updatePurchasePrice(Request $request, Supplier $supplier, Product $product)
    {
        $request->validate([
            'purchase_price' => 'required|numeric|min:0',
        ]);

        $supplier->products()->updateExistingPivot($product->id, [
            'purchase_price' => $request->purchase_price,
        ]);

        return back()->with('success', 'Purchase price updated');
    }

    public function exportSales(Request $request, Supplier $supplier)
    {
        if (!$supplier->isBuyer()) {
            abort(404);
        }

        $startDate = $request->get('sales_start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('sales_end_date', now()->format('Y-m-d'));
        $storeId = $request->get('sales_store_id');

        $filename = sprintf(
            'ventes_%s_%s_%s.xlsx',
            str_replace(' ', '_', strtolower($supplier->name)),
            $startDate,
            $endDate
        );

        return Excel::download(
            new SupplierSalesExport($supplier, $startDate, $endDate, $storeId),
            $filename
        );
    }
}
