<?php

namespace App\Http\Controllers;

use App\Models\GeneralInvoice;
use App\Models\SupplierOrder;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Date sélectionnée (par défaut aujourd'hui)
        $selectedDate = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::now();

        // Factures à payer - GeneralInvoice avec status 'pending'
        $generalInvoicesCount = GeneralInvoice::where('status', 'pending')->count();
        $generalInvoicesTotal = GeneralInvoice::where('status', 'pending')->sum('amount');

        // Factures à payer - SupplierOrder reçues non payées (uniquement type 'buyer')
        $supplierOrdersQuery = SupplierOrder::where('status', 'received')
            ->whereHas('supplier', fn($q) => $q->where('type', 'buyer'))
            ->where('is_paid', false)
            ->with('products');

        $supplierOrdersCount = $supplierOrdersQuery->count();
        $supplierOrdersTotal = $supplierOrdersQuery->get()->sum(fn($order) =>
            $order->products->sum(fn($p) => ($p->pivot->invoice_price ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_received ?? 0))
        );

        // Total des factures à payer
        $invoicesToPayCount = $generalInvoicesCount + $supplierOrdersCount;
        $invoicesToPayTotal = $generalInvoicesTotal + $supplierOrdersTotal;

        // Alertes produits - Produits sans photos
        $productsWithoutImages = Product::whereDoesntHave('images')->count();

        // Produits sans description FR
        $productsWithoutDescriptionFr = Product::where(function($q) {
            $q->whereNull('description')
              ->orWhereRaw("JSON_EXTRACT(description, '$.fr') IS NULL")
              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr')) = ''")
              ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr'))) = ''");
        })->count();

        // Produits sans description EN
        $productsWithoutDescriptionEn = Product::where(function($q) {
            $q->whereNull('description')
              ->orWhereRaw("JSON_EXTRACT(description, '$.en') IS NULL")
              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) = ''")
              ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.en'))) = ''");
        })->count();

        // Produits hors-stock - Calculer les produits avec stock total = 0
        // Méthode 1: Produits sans aucun batch
        $productsWithoutBatches = Product::whereDoesntHave('stockBatches')->pluck('id');

        // Méthode 2: Produits avec des batches mais stock total = 0
        $productsWithZeroStock = Product::whereHas('stockBatches')
            ->get()
            ->filter(function($product) {
                return $product->stockBatches()->sum('quantity') == 0;
            })
            ->pluck('id');

        // Combiner les deux listes
        $productsOutOfStock = $productsWithoutBatches->merge($productsWithZeroStock)->unique()->count();

        // Produits avec EAN fake ou vide
        $productsWithFakeOrEmptyEan = Product::where(function($q) {
            $q->where('ean', 'LIKE', 'FAKE-%')
              ->orWhereNull('ean')
              ->orWhere('ean', '');
        })->count();

        // Produits sans catégorie
        $productsWithoutCategories = Product::whereDoesntHave('categories')->count();

        // C.A. du jour sélectionné par magasin
        $startOfDay = $selectedDate->copy()->startOfDay();
        $endOfDay = $selectedDate->copy()->endOfDay();

        // C.A. du mois de la date sélectionnée par magasin
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();

        // Siem Reap (store_id = 2) - Daily
        $revenueSiemReapDaily = Sale::where('store_id', 2)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('total');
        $salesCountSiemReapDaily = Sale::where('store_id', 2)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Siem Reap (store_id = 2) - Monthly
        $revenueSiemReapMonthly = Sale::where('store_id', 2)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');
        $salesCountSiemReapMonthly = Sale::where('store_id', 2)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Phnom Penh (store_id = 1) - Daily
        $revenuePhnomPenhDaily = Sale::where('store_id', 1)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('total');
        $salesCountPhnomPenhDaily = Sale::where('store_id', 1)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Phnom Penh (store_id = 1) - Monthly
        $revenuePhnomPenhMonthly = Sale::where('store_id', 1)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');
        $salesCountPhnomPenhMonthly = Sale::where('store_id', 1)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Données pour le graphique des factures par statut
        $invoicesByStatus = [
            'to_pay' => $invoicesToPayCount,
            'paid' => GeneralInvoice::where('status', 'paid')->count() +
                      SupplierOrder::where('status', 'received')
                          ->whereHas('supplier', fn($q) => $q->where('type', 'buyer'))
                          ->where('is_paid', true)
                          ->count(),
        ];

        // Données pour le graphique du C.A. mensuel (6 derniers mois)
        $monthlyRevenue = [];
        $monthlyRevenueSiemReap = [];
        $monthlyRevenuePhnomPenh = [];
        $monthLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthLabels[] = $date->translatedFormat('M');

            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            // Total tous magasins
            $monthlyRevenue[] = Sale::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total');

            // Siem Reap (store_id = 2)
            $monthlyRevenueSiemReap[] = Sale::where('store_id', 2)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('total');

            // Phnom Penh (store_id = 1)
            $monthlyRevenuePhnomPenh[] = Sale::where('store_id', 1)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('total');
        }

        return view('dashboard', compact(
            'selectedDate',
            'invoicesToPayCount',
            'invoicesToPayTotal',
            'productsWithoutImages',
            'productsWithoutDescriptionFr',
            'productsWithoutDescriptionEn',
            'productsOutOfStock',
            'productsWithFakeOrEmptyEan',
            'productsWithoutCategories',
            'revenueSiemReapDaily',
            'salesCountSiemReapDaily',
            'revenueSiemReapMonthly',
            'salesCountSiemReapMonthly',
            'revenuePhnomPenhDaily',
            'salesCountPhnomPenhDaily',
            'revenuePhnomPenhMonthly',
            'salesCountPhnomPenhMonthly',
            'invoicesByStatus',
            'monthlyRevenue',
            'monthlyRevenueSiemReap',
            'monthlyRevenuePhnomPenh',
            'monthLabels'
        ));
    }

    public function productsWithIssues(Request $request)
    {
        $issueType = $request->get('type', 'all');

        $query = Product::with('brand', 'images', 'categories');

        // Filtrer selon le type de problème
        switch ($issueType) {
            case 'no_image':
                $query->whereDoesntHave('images');
                break;

            case 'no_description_fr':
                $query->where(function($q) {
                    $q->whereNull('description')
                      ->orWhereRaw("JSON_EXTRACT(description, '$.fr') IS NULL")
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr')) = ''")
                      ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr'))) = ''");
                });
                break;

            case 'no_description_en':
                $query->where(function($q) {
                    $q->whereNull('description')
                      ->orWhereRaw("JSON_EXTRACT(description, '$.en') IS NULL")
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) = ''")
                      ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.en'))) = ''");
                });
                break;

            case 'fake_or_empty_ean':
                $query->where(function($q) {
                    $q->where('ean', 'LIKE', 'FAKE-%')
                      ->orWhereNull('ean')
                      ->orWhere('ean', '');
                });
                break;

            case 'no_category':
                $query->whereDoesntHave('categories');
                break;

            case 'all':
            default:
                // Tous les produits avec au moins un problème
                $query->where(function($mainQuery) {
                    $mainQuery->whereDoesntHave('images')
                        ->orWhere(function($q) {
                            $q->whereNull('description')
                              ->orWhereRaw("JSON_EXTRACT(description, '$.fr') IS NULL")
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr')) = ''")
                              ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.fr'))) = ''");
                        })
                        ->orWhere(function($q) {
                            $q->whereNull('description')
                              ->orWhereRaw("JSON_EXTRACT(description, '$.en') IS NULL")
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) = ''")
                              ->orWhereRaw("TRIM(JSON_UNQUOTE(JSON_EXTRACT(description, '$.en'))) = ''");
                        })
                        ->orWhere(function($q) {
                            $q->where('ean', 'LIKE', 'FAKE-%')
                              ->orWhereNull('ean')
                              ->orWhere('ean', '');
                        })
                        ->orWhereDoesntHave('categories');
                });
                break;
        }

        $products = $query->paginate(50)->withQueryString();

        // Ajouter les problèmes pour chaque produit
        $products->getCollection()->transform(function($product) {
            $issues = [];

            if ($product->images->isEmpty()) {
                $issues[] = 'no_image';
            }

            $descFr = $product->description['fr'] ?? '';
            if (empty(trim($descFr))) {
                $issues[] = 'no_description_fr';
            }

            $descEn = $product->description['en'] ?? '';
            if (empty(trim($descEn))) {
                $issues[] = 'no_description_en';
            }

            // Vérifier si l'EAN est fake ou vide
            if (empty($product->ean) || str_starts_with($product->ean, 'FAKE-')) {
                $issues[] = 'fake_or_empty_ean';
            }

            // Vérifier si le produit n'a pas de catégorie
            if ($product->categories->isEmpty()) {
                $issues[] = 'no_category';
            }

            $product->issues = $issues;
            return $product;
        });

        return view('dashboard.products-issues', compact('products', 'issueType'));
    }

    public function dailySales(Request $request, Store $store)
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::now();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $sales = Sale::where('store_id', $store->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->with(['items.product', 'shift.user', 'exchanges.items.product', 'exchanges.generatedVoucher'])
            ->orderByDesc('created_at')
            ->get();

        // Calculer les totaux
        $totalRevenue = $sales->sum('total');
        $totalBeforeDiscount = 0;
        $totalDiscounts = 0;

        foreach ($sales as $sale) {
            $saleBeforeDiscount = 0;
            $saleItemDiscounts = 0;

            foreach ($sale->items as $item) {
                $itemTotal = $item->price * $item->quantity;
                $saleBeforeDiscount += $itemTotal;

                // Calculer les réductions au niveau article
                if (!empty($item->discounts)) {
                    foreach ($item->discounts as $discount) {
                        if (($discount['type'] ?? '') === 'percentage') {
                            $saleItemDiscounts += $itemTotal * (($discount['value'] ?? 0) / 100);
                        } else {
                            $saleItemDiscounts += ($discount['value'] ?? 0);
                        }
                    }
                }
            }

            // Calculer les réductions au niveau vente
            $saleDiscounts = 0;
            if (!empty($sale->discounts)) {
                $subtotal = $saleBeforeDiscount - $saleItemDiscounts;
                foreach ($sale->discounts as $discount) {
                    if (($discount['type'] ?? '') === 'percentage') {
                        $saleDiscounts += $subtotal * (($discount['value'] ?? 0) / 100);
                    } else {
                        $saleDiscounts += ($discount['value'] ?? 0);
                    }
                }
            }

            $totalBeforeDiscount += $saleBeforeDiscount;
            $totalDiscounts += $saleItemDiscounts + $saleDiscounts;
        }

        return view('dashboard.daily-sales', compact(
            'store',
            'sales',
            'totalRevenue',
            'totalBeforeDiscount',
            'totalDiscounts',
            'date'
        ));
    }
}